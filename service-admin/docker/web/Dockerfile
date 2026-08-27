FROM node:24.19.0-alpine3.23@sha256:244cc2b53f46f9e876304391d17682b0ddae9ac33491f4857e25e35a36ba7995 AS asset-env

WORKDIR /app

COPY --link service-admin/package.json package-lock.json ./

RUN npm install --omit=optional --omit=dev --frozen-lockfile

COPY --link service-admin/web/assets web/assets
COPY --link service-admin/public/.htaccess public/.htaccess
COPY --link service-admin/public/robots.txt public/robots.txt
RUN npm run build

FROM nginx:1.31.3-alpine3.24@sha256:818f487daa916d5bad276afac9ec1b219cdad6fd3901c39aee0efaf150b14606

# Remove unused packages
RUN apk del freetype nginx-module-image-filter curl

# Upgrade vulnerable packages
RUN apk upgrade --no-cache

RUN chown nginx:nginx /etc/nginx/conf.d

USER nginx
COPY --from=asset-env --chown=nginx:nginx /app/public /web
COPY --chown=nginx:nginx service-admin/docker/web/etc /etc
COPY --chown=nginx:nginx service-admin/docker/web/nginx.conf /etc/nginx/nginx.conf
