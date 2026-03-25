FROM golang:1.26-alpine3.23 AS build

WORKDIR /app
#ADD go.sum when using deps
COPY service-api-go/go.mod ./
RUN go mod download
COPY service-api-go/ .

RUN CGO_ENABLED=0 go build -o /app/api-app-go -ldflags="-w -s"

FROM golang:1.26-alpine3.23 AS development

RUN adduser -D -g '' appuser && \
    go install github.com/air-verse/air@latest

WORKDIR /app
USER appuser
EXPOSE 8080
ENTRYPOINT ["air"]

FROM alpine:3.23 AS production

RUN adduser -D -g '' appuser

COPY --from=build /app/api-app-go /api-app-go

WORKDIR /app
USER appuser
EXPOSE 8080
ENTRYPOINT ["/api-app-go"]
