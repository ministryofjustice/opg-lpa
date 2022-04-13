FROM nginx:stable-alpine

RUN chown -R nginx:nginx /var/cache/nginx && \
        chown -R nginx:nginx /var/log/nginx && \
        chown -R nginx:nginx /etc/nginx/conf.d && \
        touch /run/nginx.pid && \
        chown -R nginx:nginx /run/nginx.pid

RUN chmod o+rwx /etc/nginx/conf.d

# Add Confd to configure nginx on start
ENV CONFD_VERSION="0.16.0"
RUN wget -q -O /usr/local/bin/confd "https://github.com/kelseyhightower/confd/releases/download/v${CONFD_VERSION}/confd-${CONFD_VERSION}-linux-amd64" \
  && chown -R nginx:nginx /usr/local/bin/confd \
  && chmod +x /usr/local/bin/confd

COPY service-front/public /web
COPY service-front/docker/web/etc /etc

CMD confd -onetime -backend env \
  && nginx -g "daemon off;"
