package handlers

import (
	"crypto/rsa"
	"encoding/json"
	"net/http"
	"time"

	"github.com/golang-jwt/jwt/v5"
)

type tokenRequest struct {
	Email string `json:"email"`
}

type tokenResponse struct {
	IdToken string `json:"id_token"`
}

// CognitoClaims mirrors the claims shape of a real Cognito id_token
type CognitoClaims struct {
	Email           string `json:"email"`
	CognitoUsername string `json:"cognito:username"`
	TokenUse        string `json:"token_use"`
	jwt.RegisteredClaims
}

// TestTokenHandler accepts an email and returns a signed token with Cognito claims. For testing purposes only,
// it does not validate the email or require a password.
func TestTokenHandler(priv *rsa.PrivateKey, issuer string, clientID string) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var req tokenRequest
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.Email == "" {
			http.Error(w, "invalid request body", http.StatusBadRequest)
			return
		}

		now := time.Now()
		claims := CognitoClaims{
			Email:           req.Email,
			CognitoUsername: req.Email,
			TokenUse:        "id",
			RegisteredClaims: jwt.RegisteredClaims{
				Subject:   req.Email,
				Issuer:    issuer,
				Audience:  jwt.ClaimStrings{clientID},
				IssuedAt:  jwt.NewNumericDate(now),
				ExpiresAt: jwt.NewNumericDate(now.Add(1 * time.Hour)),
			},
		}

		token := jwt.NewWithClaims(jwt.SigningMethodRS256, claims)
		token.Header["kid"] = KeyID

		signed, err := token.SignedString(priv)
		if err != nil {
			http.Error(w, "failed to sign token", http.StatusInternalServerError)
			return
		}

		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(tokenResponse{IdToken: signed})
	}
}
