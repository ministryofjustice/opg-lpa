FROM node:24.11.1-alpine3.23@sha256:682368d8253e0c3364b803956085c456a612d738bd635926d73fa24db3ce53d7 AS asset-env

WORKDIR /app

COPY --link service-admin/package.json package-lock.json ./

RUN npm install --omit=optional --omit=dev --frozen-lockfile

COPY --link service-admin/web/assets web/assets
COPY --link service-admin/public/.htaccess public/.htaccess
COPY --link service-admin/public/robots.txt public/robots.txt
RUN npm run build

FROM nginx:1.31.2-alpine3.23@sha256:54f2a904c251d5a34adf545a72d32515a15e08418dae0266e23be2e18c66fefa

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
