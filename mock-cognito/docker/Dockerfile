FROM golang:1.27.0-alpine@sha256:4c9fe60190a2a3350ddc51de80d0224b8a6698d12bdfc999fee45ea9d6c46dbc AS build

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
