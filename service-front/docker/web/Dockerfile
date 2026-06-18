FROM nginx:1.31.2-alpine3.23@sha256:81595dd77c2cc4ec66c6721daa3c13b6a1f7bb3a8a2cd3247a874e3bd5c39dd2

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
