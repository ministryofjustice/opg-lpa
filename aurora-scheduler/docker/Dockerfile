ARG DISTROLESS_VERSION=latest@sha256:3592aa8171c77482f62bbc4164e6a2d141c6122554ace66e5cc910cadb961ff0
ARG GO_VERSION=1.26.4@sha256:792443b89f65105abba56b9bd5e97f680a80074ac62fc844a584212f8c8102c3

FROM golang:${GO_VERSION} AS build

WORKDIR /usr/src/app

COPY aurora-scheduler/ .

RUN go get && GOARCH=amd64 GOOS=linux CGO_ENABLED=0 go build -o /tmp/aurora-scheduler main.go

FROM gcr.io/distroless/static:${DISTROLESS_VERSION}

COPY --chmod=555 --from=build /tmp/aurora-scheduler /usr/local/bin/app

CMD ["/usr/local/bin/app"]
