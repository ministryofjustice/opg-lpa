# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:19-alpine

RUN apk upgrade --no-cache
RUN apk add --no-cache ruby
RUN npm install -g npm

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
