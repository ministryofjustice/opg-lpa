FROM nginx:stable-alpine3.23@sha256:67cbbcc757a6ce913b33e515b020cfd3923897ad883c452903b1e3487a95a570

RUN chown -R nginx:nginx /var/cache/nginx && \
    chown -R nginx:nginx /var/log/nginx && \
    touch /run/nginx.pid && \
    chown -R nginx:nginx /run/nginx.pid && \
    chmod o+rwx /etc/nginx/conf.d

# Remove unused packages
RUN apk del freetype nginx-module-image-filter curl

# Upgrade vulnerable packages
RUN apk upgrade --no-cache

USER nginx
COPY --chown=nginx:nginx service-api/docker/web/etc /etc
VOLUME [ "/etc" ]
