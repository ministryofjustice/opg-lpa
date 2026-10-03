FROM nginx:1.31.6-alpine3.24@sha256:527de634221553670afa2617c96625cd0f860000f3525169b100f41c5b9b8213

# Remove unused packages
RUN apk del freetype nginx-module-image-filter

# Upgrade vulnerable packages
RUN apk upgrade --no-cache

RUN chown -R nginx:nginx /etc/nginx/conf.d

USER nginx
COPY --chown=nginx:nginx service-api/docker/web/etc /etc
COPY --chown=nginx:nginx service-api/docker/web/nginx.conf /etc/nginx/nginx.conf
