# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:20-alpine3.21@sha256:f838a8eabffcfbdf4276294401a56e6e1ce02a638865a1f6ef1e9556f3bd0786

RUN apk upgrade --no-cache
RUN apk add --no-cache ruby

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
