# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM alpine:latest

RUN apk add ruby npm

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
