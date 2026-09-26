FROM nginx:1.31.5-alpine3.24@sha256:af1ffb5b318f86b062b5e11330af1b831e40ff19933bffd520971df99a01cb2f

# Remove unused packages
# RUN apk del freetype nginx-module-image-filter curl
RUN apk del freetype nginx-module-image-filter

# Upgrade vulnerable packages
RUN apk upgrade --no-cache

RUN chown -R nginx:nginx /etc/nginx/conf.d

USER nginx
COPY --chown=nginx:nginx service-front/public /web
COPY --chown=nginx:nginx service-front/docker/web/etc /etc
COPY --chown=nginx:nginx service-front/docker/web/nginx.conf /etc/nginx/nginx.conf
COPY --chown=nginx:nginx service-front/docker/web/custom_50x.html /usr/share/nginx/html
