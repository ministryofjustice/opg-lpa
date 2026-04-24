FROM nginx:1.30.0-alpine3.23@sha256:0272e4604ed93c1792f03695a033a6e8546840f86e0de20a884bb17d2c924883

RUN chown -R nginx:nginx /var/cache/nginx && \
    chown -R nginx:nginx /var/log/nginx && \
    chown -R nginx:nginx /etc/nginx/conf.d && \
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
