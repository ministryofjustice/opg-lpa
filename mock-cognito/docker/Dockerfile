FROM golang:1.27.3-alpine@sha256:d530cf60723abf1ffae0cd16bb546b379ec0550d2f3815e5b16ed69dbc00fba4 AS build

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
