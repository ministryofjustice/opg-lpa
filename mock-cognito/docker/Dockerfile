FROM golang:1.27.1-alpine@sha256:8a5910f31396cd4d89662f56c68b3ae31d374308270a1c3bd96672ee5ed43414 AS build

WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 GOOS=linux go build -o /mock-cognito .

RUN CGO_ENABLED=0 GOOS=linux go build -o /healthcheck ./docker/healthcheck.go

FROM scratch AS production

COPY --from=build /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=build /mock-cognito /mock-cognito
COPY --from=build /healthcheck /healthcheck

EXPOSE 8080
ENTRYPOINT ["/mock-cognito"]
