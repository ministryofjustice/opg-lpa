# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:20-alpine3.21@sha256:48022836f3fbf7d8cd398114b5091cbe3c4b6cd5a4f37f0e5b2aece7fd6d2fc4

RUN apk upgrade --no-cache
RUN apk add --no-cache ruby
RUN npm install -g npm

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
