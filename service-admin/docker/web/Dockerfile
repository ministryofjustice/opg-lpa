FROM node:24.21.0-alpine3.23@sha256:9ec4a2e289874ed0d722e1772ec2de45d2801541db8612f3638b26f128c69ac2 AS asset-env

WORKDIR /app

COPY --link service-admin/package.json package-lock.json ./

RUN npm install --omit=optional --omit=dev --frozen-lockfile

COPY --link service-admin/web/assets web/assets
COPY --link service-admin/public/.htaccess public/.htaccess
COPY --link service-admin/public/robots.txt public/robots.txt
RUN npm run build

FROM nginx:1.31.5-alpine3.24@sha256:af1ffb5b318f86b062b5e11330af1b831e40ff19933bffd520971df99a01cb2f

# Remove unused packages
RUN apk del freetype nginx-module-image-filter

# Upgrade vulnerable packages
RUN apk upgrade --no-cache

RUN chown -R nginx:nginx /etc/nginx/conf.d

USER nginx
COPY --from=asset-env --chown=nginx:nginx /app/public /web
COPY --chown=nginx:nginx service-admin/docker/web/etc /etc
COPY --chown=nginx:nginx service-admin/docker/web/nginx.conf /etc/nginx/nginx.conf
