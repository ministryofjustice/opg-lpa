FROM node:24.11.1-alpine3.23@sha256:682368d8253e0c3364b803956085c456a612d738bd635926d73fa24db3ce53d7 AS asset-env

# TODO Remove this once alpine upgrade available as it patches a vulnerability
RUN apk update && apk add --upgrade "zlib==1.3.2-r1"

WORKDIR /app

COPY --link service-admin/package.json package-lock.json ./

RUN npm install --omit=optional --omit=dev --frozen-lockfile

COPY --link service-admin/web/assets web/assets
COPY --link service-admin/public/.htaccess public/.htaccess
COPY --link service-admin/public/robots.txt public/robots.txt
RUN npm run build

FROM nginx:1.31.3-alpine3.24@sha256:818f487daa916d5bad276afac9ec1b219cdad6fd3901c39aee0efaf150b14606

# Remove unused packages
RUN apk del freetype nginx-module-image-filter

# Upgrade vulnerable packages
RUN apk upgrade --no-cache
# TODO Remove this once alpine upgrade available as it patches a vulnerability
RUN apk update && apk add --upgrade "zlib==1.3.2-r1"

RUN chown -R nginx:nginx /etc/nginx/conf.d

USER nginx
COPY --from=asset-env --chown=nginx:nginx /app/public /web
COPY --chown=nginx:nginx service-admin/docker/web/etc /etc
COPY --chown=nginx:nginx service-admin/docker/web/nginx.conf /etc/nginx/nginx.conf
