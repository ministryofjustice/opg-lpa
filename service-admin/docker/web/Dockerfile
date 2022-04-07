FROM nginx:stable-alpine

RUN adduser -D -g '' user
# Add Confd to configure nginx on start
ENV CONFD_VERSION="0.16.0"
RUN wget -q -O /usr/local/bin/confd "https://github.com/kelseyhightower/confd/releases/download/v${CONFD_VERSION}/confd-${CONFD_VERSION}-linux-amd64" \
  && chmod +x /usr/local/bin/confd

COPY --chown=user:user service-admin/public /web
COPY --chown=user:user service-admin/docker/web/etc /etc

USER "user"
CMD sudo confd -onetime -backend env \
  && sudo nginx -g "daemon off;"
