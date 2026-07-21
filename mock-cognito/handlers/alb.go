package handlers

import (
	"crypto/ecdsa"
	"crypto/x509"
	"encoding/json"
	"encoding/pem"
	"net/http"
	"time"

	"github.com/golang-jwt/jwt/v5"
)

// KeyID identifies the mock ALB signing key, mirroring the "kid" AWS assigns to a
// real Application Load Balancer's signing key.
const KeyID = "mock-alb-key-1"

// PublicKeyHandler mimics AWS's https://public-keys.auth.elb.<region>.amazonaws.com/<kid>
// endpoint: given a key ID it returns the PEM-encoded public key, or 404 for any other kid
// (e.g. after simulating key rotation).
func PublicKeyHandler(pub *ecdsa.PublicKey) http.HandlerFunc {
	derBytes, err := x509.MarshalPKIXPublicKey(pub)
	if err != nil {
		panic(err)
	}
	pemBytes := pem.EncodeToMemory(&pem.Block{Type: "PUBLIC KEY", Bytes: derBytes})

	return func(w http.ResponseWriter, r *http.Request) {
		if r.PathValue("kid") != KeyID {
			http.NotFound(w, r)
			return
		}

		w.Header().Set("Content-Type", "application/x-pem-file")
		w.Write(pemBytes)
	}
}

type tokenRequest struct {
	Email string `json:"email"`
}

type tokenResponse struct {
	Token string `json:"token"`
}

// AlbTokenHandler accepts an email and returns a token signed the same way a real
// Application Load Balancer signs its X-Amzn-Oidc-Data header: ES256, with "kid" and
// "signer" fields in the JWT header, and plain user-info claims (sub, email) in the
// payload — no token_use/iss/aud, since those only exist in the underlying Cognito ID
// token which the ALB does not forward to the target.
//
// For testing purposes only: it does not validate the email or require a password.
func AlbTokenHandler(priv *ecdsa.PrivateKey, signerArn string, clientID string) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var req tokenRequest
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.Email == "" {
			http.Error(w, "invalid request body", http.StatusBadRequest)
			return
		}

		now := time.Now()
		claims := jwt.MapClaims{
			"sub":   req.Email,
			"email": req.Email,
			"exp":   jwt.NewNumericDate(now.Add(1 * time.Hour)).Unix(),
		}

		token := jwt.NewWithClaims(jwt.SigningMethodES256, claims)
		token.Header["kid"] = KeyID
		token.Header["signer"] = signerArn
		token.Header["client"] = clientID

		signed, err := token.SignedString(priv)
		if err != nil {
			http.Error(w, "failed to sign token", http.StatusInternalServerError)
			return
		}

		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(tokenResponse{Token: signed})
	}
}

// HealthHandler is used by the container healthcheck.
func HealthHandler() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		w.Write([]byte("ok"))
	}
}

// LogoutHandler mimics Cognito's hosted-UI GET /logout endpoint: it clears the
// (nonexistent, in this mock) hosted-UI session and redirects to the logout_uri query
// param, exactly as real Cognito does after admin-app's SignOutHandler redirects here.
func LogoutHandler() http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		logoutURI := r.URL.Query().Get("logout_uri")
		if logoutURI == "" {
			http.Error(w, "missing logout_uri query param", http.StatusBadRequest)
			return
		}

		http.Redirect(w, r, logoutURI, http.StatusFound)
	}
}
