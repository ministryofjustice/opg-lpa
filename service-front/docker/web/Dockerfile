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
COPY --chown=nginx:nginx service-front/public /web
COPY --chown=nginx:nginx service-front/docker/web/etc /etc
COPY --chown=nginx:nginx service-front/docker/web/nginx.conf /etc/nginx/nginx.conf
COPY --chown=nginx:nginx service-front/docker/web/custom_50x.html /usr/share/nginx/html
VOLUME [ "/etc" ]
