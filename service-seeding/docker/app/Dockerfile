FROM alpine:latest

RUN apk add --update --no-cache postgresql-client && \
    apk upgrade --no-cache

RUN rm -rf /var/cache/apk/* /tmp/*

COPY scripts/non_live_seeding /app

WORKDIR /app

CMD sh /app/seed_environment.sh
