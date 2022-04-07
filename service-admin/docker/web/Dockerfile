FROM nginx:stable-alpine

RUN chown -R nginx:nginx /var/cache/nginx && \
        chown -R nginx:nginx /var/log/nginx && \
        chown -R nginx:nginx /etc/nginx/conf.d && \
        mkdir /usr/local/bin/confd && \
        chown -R nginx:nginx /usr/local/bin/confd
RUN chmod -R 777 /etc/nginx/conf.d && \
    chmod -R 777 /usr/local/bin/confd
USER nginx

# Add Confd to configure nginx on start
ENV CONFD_VERSION="0.16.0"
RUN  wget -q -P /usr/local/bin/confd "https://github.com/kelseyhightower/confd/releases/download/v${CONFD_VERSION}/confd-${CONFD_VERSION}-linux-amd64" \
  && chmod +x /usr/local/bin/confd

COPY --chown=nginx:nginx service-admin/public /web
COPY --chown=nginx:nginx service-admin/docker/web/etc /etc

CMD /usr/local/bin/confd -onetime -backend env \
  && nginx -g "daemon off;"
