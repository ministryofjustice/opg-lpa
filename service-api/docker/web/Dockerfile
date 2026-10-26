FROM nginx:1.31.8-alpine3.24@sha256:a3554c0d0b48177b1413c26e01f695334428020d9df01243fbae3e11cfd17417

# Remove unused packages
RUN apk del freetype nginx-module-image-filter

# Upgrade vulnerable packages
RUN apk upgrade --no-cache
# TODO Remove this once alpine upgrade available as it patches a vulnerability
RUN apk update && apk add --upgrade "zlib==1.3.2-r1"

RUN chown -R nginx:nginx /etc/nginx/conf.d

USER nginx
COPY --chown=nginx:nginx service-api/docker/web/etc /etc
COPY --chown=nginx:nginx service-api/docker/web/nginx.conf /etc/nginx/nginx.conf
