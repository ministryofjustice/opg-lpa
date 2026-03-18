ARG DISTROLESS_VERSION=latest@sha256:28efbe90d0b2f2a3ee465cc5b44f3f2cf5533514cf4d51447a977a5dc8e526d0
ARG GO_VERSION=1.26.1@sha256:c7e98cc0fd4dfb71ee7465fee6c9a5f079163307e4bf141b336bb9dae00159a5

FROM golang:${GO_VERSION} AS build

WORKDIR /usr/src/app

COPY aurora-scheduler/ .

RUN go get && GOARCH=amd64 GOOS=linux CGO_ENABLED=0 go build -o /tmp/aurora-scheduler main.go

FROM gcr.io/distroless/static:${DISTROLESS_VERSION}

COPY --chmod=555 --from=build /tmp/aurora-scheduler /usr/local/bin/app

CMD ["/usr/local/bin/app"]
