# This is only used locally, and depends on the service-front
# directory being mounted into this image at /service-front
FROM node:22-alpine3.21@sha256:f40aebdd0c1959821ab6d72daecafb2cd1d4c9a958e9952c1d71b84d4458f875

RUN apk upgrade --no-cache
# hadolint ignore=DL3018
RUN apk add --no-cache ruby

WORKDIR /service-front

COPY ./node-build-assets/docker/start.sh /app/

RUN chmod +x /app/start.sh

CMD ["/app/start.sh"]
