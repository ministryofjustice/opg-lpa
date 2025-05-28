# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:20-alpine3.21@sha256:be56e91681a8ec1bba91e3006039bd228dc797fd984794a3efedab325b36e679

RUN apk upgrade --no-cache
RUN apk add --no-cache ruby
RUN npm install -g npm

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
