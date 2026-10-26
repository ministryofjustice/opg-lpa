FROM nginx:1.31.7-alpine3.24@sha256:7ddef4fb1b16816185c5e3ca4c76086e2db0d37f2e9eee57b9a3fb5182cca562

# Remove unused packages
# RUN apk del freetype nginx-module-image-filter curl
RUN apk del freetype nginx-module-image-filter

# Upgrade vulnerable packages
RUN apk upgrade --no-cache
# TODO Remove this once alpine upgrade available as it patches a vulnerability
RUN apk update && apk add --upgrade "zlib==1.3.2-r1" "ca-certificates==20261020-r0"

RUN chown -R nginx:nginx /etc/nginx/conf.d

USER nginx
COPY --chown=nginx:nginx service-front/public /web
COPY --chown=nginx:nginx service-front/docker/web/etc /etc
COPY --chown=nginx:nginx service-front/docker/web/nginx.conf /etc/nginx/nginx.conf
COPY --chown=nginx:nginx service-front/docker/web/custom_50x.html /usr/share/nginx/html
