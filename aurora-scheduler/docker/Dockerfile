ARG DISTROLESS_VERSION=latest@sha256:47b2d72ff90843eb8a768b5c2f89b40741843b639d065b9b937b07cd59b479c6
ARG GO_VERSION=1.26.2@sha256:fcdb3e42c5544e9682a635771eac76a698b66de79b1b50ec5b9ce5c5f14ad775

FROM golang:${GO_VERSION} AS build

WORKDIR /usr/src/app

COPY aurora-scheduler/ .

RUN go get && GOARCH=amd64 GOOS=linux CGO_ENABLED=0 go build -o /tmp/aurora-scheduler main.go

FROM gcr.io/distroless/static:${DISTROLESS_VERSION}

COPY --chmod=555 --from=build /tmp/aurora-scheduler /usr/local/bin/app

CMD ["/usr/local/bin/app"]
