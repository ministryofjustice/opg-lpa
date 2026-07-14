package main

import (
	"crypto/rand"
	"crypto/rsa"
	"log"
	"net/http"
	"os"

	"github.com/ministryofjustice/opg-lpa/mock-cognito/handlers"
)

func main() {
	privateKey, err := rsa.GenerateKey(rand.Reader, 2048)
	if err != nil {
		log.Fatal(err)
	}

	log.Println("keys generated successfully")

	port := os.Getenv("PORT")
	issuer := os.Getenv("COGNITO_MOCK_ISSUER")
	clientID := os.Getenv("COGNITO_CLIENT_ID")

	mux := http.NewServeMux()
	mux.HandleFunc("GET /.well-known/jwks.json", handlers.JwksHandler(&privateKey.PublicKey))
	mux.HandleFunc("POST /test/token", handlers.TestTokenHandler(privateKey, issuer, clientID))

	log.Printf("Starting mock Cognito server on port %s", port)
	if err := http.ListenAndServe(":"+port, mux); err != nil {
		log.Fatal(err)
	}
}
