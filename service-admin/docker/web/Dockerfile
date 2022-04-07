FROM nginx:stable-alpine

RUN chown -R nginx:nginx /var/cache/nginx && \
        chown -R nginx:nginx /var/log/nginx && \
        chown -R nginx:nginx /etc/nginx/conf.d
RUN chmod -R 777 /etc/nginx/conf.d


# Add Confd to configure nginx on start
ENV CONFD_VERSION="0.16.0"
RUN  wget -q -O /usr/local/bin/confd "https://github.com/kelseyhightower/confd/releases/download/v${CONFD_VERSION}/confd-${CONFD_VERSION}-linux-amd64" \
  && chmod u+rwx /usr/local/bin/confd \
  && chmod u+x /bin/sh

USER nginx
COPY --chown=nginx:nginx service-admin/public /web
COPY --chown=nginx:nginx service-admin/docker/web/etc /etc

CMD confd -onetime -backend env \
  && nginx -g "daemon off;"
