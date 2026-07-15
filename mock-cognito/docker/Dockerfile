FROM golang:1.26.4-alpine@sha256:3ad57304ad93bbec8548a0437ad9e06a455660655d9af011d58b993f6f615648 AS build

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
