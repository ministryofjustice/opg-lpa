package keys

import (
	"crypto/rand"
	"crypto/rsa"
	"fmt"
)

const KeyID = "mock-cognito-key-1"

type KeyPair struct {
	Private *rsa.PrivateKey
	Public  *rsa.PublicKey
}

func Generate() (*KeyPair, error) {
	private, err := rsa.GenerateKey(rand.Reader, 2048)
	if err != nil {
		return nil, fmt.Errorf("failed to generate RSA key: %s", err)
	}

	return &KeyPair{private, &private.PublicKey}, nil
}
