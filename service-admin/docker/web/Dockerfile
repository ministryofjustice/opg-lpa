FROM node:22.21.1-alpine3.21@sha256:af8023ec879993821f6d5b21382ed915622a1b0f1cc03dbeb6804afaf01f8885 AS asset-env

WORKDIR /app

COPY --link service-admin/package.json package-lock.json ./

RUN npm install --omit=optional --omit=dev --frozen-lockfile

COPY --link service-admin/web/assets web/assets
COPY --link service-admin/public/.htaccess public/.htaccess
COPY --link service-admin/public/robots.txt public/robots.txt
RUN npm run build

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
COPY --from=asset-env --chown=nginx:nginx /app/public /web
COPY --chown=nginx:nginx service-admin/docker/web/etc /etc
VOLUME [ "/etc" ]
