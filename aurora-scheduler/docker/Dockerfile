ARG DISTROLESS_VERSION=latest@sha256:b7b9a6953e7bed6baaf37329331051d7bdc1b99c885f6dbeb72d75b1baad54f9
ARG GO_VERSION=1.24@sha256:764d7e0ce1df1e4a1bddc6d1def5f3516fdc045c5fad88e61f67fdbd1857282f

FROM golang:${GO_VERSION} as build

WORKDIR /usr/src/app

COPY aurora-scheduler/ .

RUN go get && GOARCH=amd64 GOOS=linux CGO_ENABLED=0 go build -o /tmp/aurora-scheduler main.go 

FROM gcr.io/distroless/static:${DISTROLESS_VERSION}

COPY --chmod=555 --from=build /tmp/aurora-scheduler /usr/local/bin/app

CMD ["/usr/local/bin/app"]