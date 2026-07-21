package main

import (
	"fmt"
	"log"
	"net/http"
	"os"
)

func main() {
	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
	}

	resp, err := http.Get(fmt.Sprintf("http://localhost:%s/health", port))
	if err != nil {
		log.Printf("Health check failed: %v", err)
		os.Exit(1)
	}

	if resp.StatusCode != http.StatusOK {
		log.Printf("Health check failed: received status code %d", resp.StatusCode)
		os.Exit(1)
	}

	log.Printf("Health check passed")
	os.Exit(0)
}
