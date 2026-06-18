FROM golang:1.26.4-alpine@sha256:3ad57304ad93bbec8548a0437ad9e06a455660655d9af011d58b993f6f615648 AS base

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
