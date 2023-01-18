FROM alpine:latest

RUN apk add --update --no-cache postgresql-client && \
    apk upgrade

RUN rm -rf /var/cache/apk/* /tmp/* && chmod 766 /tmp/
VOLUME ["/tmp"]

COPY scripts/non_live_seeding /app

WORKDIR /app

CMD chmod +x seed_environment.sh && ./seed_environment.sh
