# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:24-alpine3.21@sha256:0d4cd385375043fc4612c20ede8b240b6b560dfdaef7cdaeff8e440c6a5b8499

RUN apk upgrade --no-cache
RUN apk add --no-cache ruby

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
