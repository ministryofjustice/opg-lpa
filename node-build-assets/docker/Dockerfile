# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:22-alpine3.21@sha256:af8023ec879993821f6d5b21382ed915622a1b0f1cc03dbeb6804afaf01f8885

RUN apk upgrade --no-cache
RUN apk add --no-cache ruby

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
