# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:20-alpine3.21@sha256:82f7e381b009b4adc79cef79ec296896788bb91e522d277048b2d8ff6d976393

RUN apk upgrade --no-cache
RUN apk add --no-cache ruby
RUN npm install -g npm

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
