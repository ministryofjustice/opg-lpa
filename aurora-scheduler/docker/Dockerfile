ARG DISTROLESS_VERSION=latest@sha256:b7b9a6953e7bed6baaf37329331051d7bdc1b99c885f6dbeb72d75b1baad54f9
ARG GO_VERSION=1.24@sha256:20a022e5112a144aa7b7aeb3f22ebf2cdaefcc4aac0d64e8deeee8cdc18b9c0f

FROM golang:${GO_VERSION} as build

WORKDIR /usr/src/app

COPY aurora-scheduler/ .

RUN go get && GOARCH=amd64 GOOS=linux CGO_ENABLED=0 go build -o /tmp/aurora-scheduler main.go 

FROM gcr.io/distroless/static:${DISTROLESS_VERSION}

COPY --chmod=555 --from=build /tmp/aurora-scheduler /usr/local/bin/app

CMD ["/usr/local/bin/app"]