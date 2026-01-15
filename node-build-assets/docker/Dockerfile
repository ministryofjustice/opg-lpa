# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:24.13.0-alpine3.22@sha256:6f96670faefc1ec23520ce30e453e85bfdb94a43b49b7aac2b0d3ac3a902bf2f
RUN apk upgrade --no-cache
RUN apk add --no-cache ruby

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
