FROM nginx:stable-alpine3.21@sha256:d83c0138ea82c9f05c4378a5001e0c71256b647603c10c186bd7697a4db722d3

RUN chown -R nginx:nginx /var/cache/nginx && \
    chown -R nginx:nginx /var/log/nginx && \
    chown -R nginx:nginx /etc/nginx/conf.d && \
    touch /run/nginx.pid && \
    chown -R nginx:nginx /run/nginx.pid && \
    chmod o+rwx /etc/nginx/conf.d

# Add Confd to configure nginx on start
ENV CONFD_VERSION="0.16.0"
RUN wget -q -O /usr/local/bin/confd "https://github.com/kelseyhightower/confd/releases/download/v${CONFD_VERSION}/confd-${CONFD_VERSION}-linux-amd64" && \
    chown -R nginx:nginx /usr/local/bin/confd && \
    chmod +x /usr/local/bin/confd

# Remove unused packages
RUN apk del freetype nginx-module-image-filter curl

# Upgrade vulnerable packages
RUN apk upgrade --no-cache

USER nginx
COPY --chown=nginx:nginx service-admin/public /web
COPY --chown=nginx:nginx shared/docker/web/etc/confd/conf.d/nginx.toml.service-admin /etc/confd/conf.d/nginx.toml
COPY --chown=nginx:nginx shared/docker/web/etc/confd/templates/nginx.conf.service-admin /etc/confd/templates/nginx.conf

CMD confd -onetime -backend env \
  && nginx -g "daemon off;"
