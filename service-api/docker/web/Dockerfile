FROM nginx:stable-alpine

# Add Confd to configure nginx on start
ENV CONFD_VERSION="0.16.0"
RUN wget -q -O /usr/local/bin/confd "https://github.com/kelseyhightower/confd/releases/download/v${CONFD_VERSION}/confd-${CONFD_VERSION}-linux-amd64" \
  && chmod +x /usr/local/bin/confd

COPY opg-lpa-api/docker/web/etc /etc

CMD confd -onetime -backend env \
  && nginx -g "daemon off;"
