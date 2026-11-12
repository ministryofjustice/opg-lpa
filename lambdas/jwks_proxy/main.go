package main

import (
	"context"
	"fmt"
	"io"
	"net/http"
	"os"
	"time"

	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-lambda-go/lambda"
)

func main() {
	handler := &Handler{
		jwksURL: os.Getenv("APP_JWKS_URL"),
		client:  &http.Client{Timeout: 10 * time.Second},
	}

	lambda.Start(handler.handle)
}

type Handler struct {
	jwksURL string
	client  *http.Client
}

func (h *Handler) handle(ctx context.Context, in events.APIGatewayProxyRequest) (events.APIGatewayProxyResponse, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, h.jwksURL, nil)
	if err != nil {
		return events.APIGatewayProxyResponse{}, fmt.Errorf("create request: %v", err)
	}

	resp, err := h.client.Do(req)
	if err != nil {
		return events.APIGatewayProxyResponse{}, fmt.Errorf("do request: %v", err)
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return events.APIGatewayProxyResponse{}, fmt.Errorf("read body: %v", err)
	}

	return events.APIGatewayProxyResponse{
		StatusCode: resp.StatusCode,
		Body:       string(body),
	}, nil
}
