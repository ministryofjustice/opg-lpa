# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:24.13-alpine3.22@sha256:d28696cabe6a72c5addbb608b344818e5a158d849174abd4b1ae85ab48536280
RUN apk upgrade --no-cache
RUN apk add --no-cache ruby

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
