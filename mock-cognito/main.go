package main

import (
	"crypto/ecdsa"
	"crypto/elliptic"
	"crypto/rand"
	"log"
	"net/http"
	"os"

	"github.com/ministryofjustice/opg-lpa/mock-cognito/handlers"
)

func main() {
	privateKey, err := ecdsa.GenerateKey(elliptic.P256(), rand.Reader)
	if err != nil {
		log.Fatal(err)
	}

	log.Println("keys generated successfully")

	port := os.Getenv("PORT")
	signerArn := os.Getenv("ADMIN_ALB_ARN")
	clientID := os.Getenv("COGNITO_CLIENT_ID")

	mux := http.NewServeMux()
	mux.HandleFunc("GET /health", handlers.HealthHandler())
	mux.HandleFunc("GET /public-keys/{kid}", handlers.PublicKeyHandler(&privateKey.PublicKey))
	mux.HandleFunc("POST /test/token", handlers.AlbTokenHandler(privateKey, signerArn, clientID))
	mux.HandleFunc("GET /logout", handlers.LogoutHandler())

	log.Printf("Starting mock ALB/Cognito server on port %s", port)
	if err := http.ListenAndServe(":"+port, mux); err != nil {
		log.Fatal(err)
	}
}
