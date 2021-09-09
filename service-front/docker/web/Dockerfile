FROM nginx:stable-alpine

# Add Confd to configure nginx on start
ENV CONFD_VERSION="0.16.0"
RUN wget -q -O /usr/local/bin/confd "https://github.com/kelseyhightower/confd/releases/download/v${CONFD_VERSION}/confd-${CONFD_VERSION}-linux-amd64" \
  && chmod +x /usr/local/bin/confd

COPY service-front/public /web
COPY service-front/docker/web/etc /etc

CMD confd -onetime -backend env \
# substitute in Front V2 URL from environment
  && sed -i "s|\$front_v2_url|http://$OPG_LPA_ENDPOINTS_FRONT_V2|g" /etc/nginx/conf.d/default.conf \
  && nginx -g "daemon off;"
