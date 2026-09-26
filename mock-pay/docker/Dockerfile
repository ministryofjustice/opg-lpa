FROM golang:1.27.0-alpine@sha256:4c9fe60190a2a3350ddc51de80d0224b8a6698d12bdfc999fee45ea9d6c46dbc AS base

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
