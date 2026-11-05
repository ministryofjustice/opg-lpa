package main

import (
	"context"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"os"
	"time"

	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-lambda-go/lambda"
	"github.com/aws/aws-sdk-go-v2/config"
)

func main() {
	var (
		ctx         = context.Background()
		logger      = slog.New(slog.NewJSONHandler(os.Stdout, nil))
		endpointURL = os.Getenv("AWS_BASE_URL")
		jwksURL     = os.Getenv("APP_JWKS_URL")
	)

	cfg, err := config.LoadDefaultConfig(ctx)
	if err != nil {
		logger.Error("failed to load aws config", slog.Any("err", err))
		return
	}

	if endpointURL != "" {
		cfg.BaseEndpoint = &endpointURL
	}

	handler := &Handler{
		logger:  logger,
		jwksURL: jwksURL,
		client:  &http.Client{Timeout: 10 * time.Second},
	}
	lambda.Start(handler.handle)
}

type Handler struct {
	logger  *slog.Logger
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
