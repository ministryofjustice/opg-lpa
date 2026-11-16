FROM golang:1.26.5-alpine3.23@sha256:622e56dbc11a8cfe87cafa2331e9a201877271cbff918af53d3be315f3da88cc AS base

ENV CGO_ENABLED=0 GOOS=linux GOARCH=amd64

WORKDIR /app

COPY lambdas/jwks_proxy/go.mod lambdas/jwks_proxy/go.sum ./
RUN go mod download

COPY lambdas/jwks_proxy/main.go ./

RUN go build -tags lambda.norpc -ldflags="-s -w" -o /go/bin/main ./

FROM gcr.io/distroless/static:nonroot@sha256:e3f945647ffb95b5839c07038d64f9811adf17308b9121d8a2b87b6a22a80a39 AS production

WORKDIR /var/task

COPY --from=base /go/bin/main ./main

ENTRYPOINT ["./main"]
