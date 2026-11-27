FROM nginx:1.31.9-alpine3.24@sha256:fb4658584bd9a916ffb6f6e365f97c25cfdb2923801f551ea08356e695f14ff7

# Remove unused packages
# RUN apk del freetype nginx-module-image-filter curl
RUN apk del freetype nginx-module-image-filter nginx-module-njs nginx-module-xslt libxslt libxml2

# Upgrade vulnerable packages
RUN apk upgrade --no-cache
# TODO Remove this once alpine upgrade available as it patches a vulnerability
RUN apk update && apk add --upgrade "zlib==1.4-r0"

RUN chown -R nginx:nginx /etc/nginx/conf.d

USER nginx
COPY --chown=nginx:nginx service-front/public /web
COPY --chown=nginx:nginx service-front/docker/web/etc /etc
COPY --chown=nginx:nginx service-front/docker/web/nginx.conf /etc/nginx/nginx.conf
COPY --chown=nginx:nginx service-front/docker/web/custom_50x.html /usr/share/nginx/html
