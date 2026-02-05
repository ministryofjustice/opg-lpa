ARG DISTROLESS_VERSION=latest@sha256:972618ca78034aaddc55864342014a96b85108c607372f7cbd0dbd1361f1d841
ARG GO_VERSION=1.25.6@sha256:ce63a16e0f7063787ebb4eb28e72d477b00b4726f79874b3205a965ffd797ab2

FROM golang:${GO_VERSION} AS build

WORKDIR /usr/src/app

COPY aurora-scheduler/ .

RUN go get && GOARCH=amd64 GOOS=linux CGO_ENABLED=0 go build -o /tmp/aurora-scheduler main.go

FROM gcr.io/distroless/static:${DISTROLESS_VERSION}

COPY --chmod=555 --from=build /tmp/aurora-scheduler /usr/local/bin/app

CMD ["/usr/local/bin/app"]
