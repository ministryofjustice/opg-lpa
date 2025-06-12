ARG DISTROLESS_VERSION=latest@sha256:d9f9472a8f4541368192d714a995eb1a99bab1f7071fc8bde261d7eda3b667d8
ARG GO_VERSION=1.24@sha256:d1db785fb37feb87d9140d78ed4fb7c75ee787360366a9c5efe39c7a841a0277

FROM golang:${GO_VERSION} as build

WORKDIR /usr/src/app

COPY aurora-scheduler/ .

RUN go get && GOARCH=amd64 GOOS=linux CGO_ENABLED=0 go build -o /tmp/aurora-scheduler main.go 

FROM gcr.io/distroless/static:${DISTROLESS_VERSION}

COPY --chmod=555 --from=build /tmp/aurora-scheduler /usr/local/bin/app

CMD ["/usr/local/bin/app"]