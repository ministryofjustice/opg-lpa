FROM nginx:1.31.2-alpine3.23@sha256:1a8724a52d432501548a8d8681bb1554c2d09778f8b9ed0882fc3442549980b7

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
COPY --chown=nginx:nginx service-front/public /web
COPY --chown=nginx:nginx service-front/docker/web/etc /etc
COPY --chown=nginx:nginx service-front/docker/web/nginx.conf /etc/nginx/nginx.conf
COPY --chown=nginx:nginx service-front/docker/web/custom_50x.html /usr/share/nginx/html
VOLUME [ "/etc" ]
