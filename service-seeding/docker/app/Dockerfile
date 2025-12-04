FROM alpine:3.23.0@sha256:51183f2cfa6320055da30872f211093f9ff1d3cf06f39a0bdb212314c5dc7375

RUN apk add --update --no-cache postgresql-client && \
    apk upgrade --no-cache

RUN rm -rf /var/cache/apk/* /tmp/*

COPY scripts/non_live_seeding /app

WORKDIR /app

CMD ["/app/seed_environment.sh"]
