FROM golang:1.26.5-alpine@sha256:0178a641fbb4858c5f1b48e34bdaabe0350a330a1b1149aabd498d0699ff5fb2 AS build

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
