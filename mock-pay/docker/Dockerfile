FROM golang:1.27.1-alpine@sha256:8a5910f31396cd4d89662f56c68b3ae31d374308270a1c3bd96672ee5ed43414 AS base

RUN addgroup -S user && \
  adduser -S -g user user

# install golang net package to fix security vulnerability (can be removed once patched in imposter-go)
RUN go mod init tmp \
  && go get github.com/imposter-project/imposter-go/cmd/imposter@v1.8.0 golang.org/x/net@v0.53.0 \
  && go install github.com/imposter-project/imposter-go/cmd/imposter \
  && mv "$GOPATH/bin/imposter" /tmp/imposter

RUN wget -q -O /tmp/publicapi_spec.json https://raw.githubusercontent.com/alphagov/pay-publicapi/master/openapi/publicapi_spec.json

FROM scratch AS production

ENV IMPOSTER_CONFIG_DIR=/opt/imposter/config
ENV IMPOSTER_PLUGIN_DIR=/opt/imposter/plugins

COPY --from=base /etc/passwd /etc/passwd
COPY --from=base /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=base /tmp/publicapi_spec.json /opt/imposter/config/
COPY --from=base /tmp/imposter /imposter-go
COPY --link ../mock-pay /opt/imposter/config/

USER user
EXPOSE 8080

ENTRYPOINT ["/imposter-go"]
