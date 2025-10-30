# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:24.11.0-alpine3.22@sha256:a3955307c0ddda257f0c8c5f1442017838cfa6d0e77ea61912be6d38f4abcd6d
RUN apk upgrade --no-cache
RUN apk add --no-cache ruby

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
