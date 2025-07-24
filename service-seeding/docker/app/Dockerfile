FROM alpine:3.22.1@sha256:4bcff63911fcb4448bd4fdacec207030997caf25e9bea4045fa6c8c44de311d1

RUN apk add --update --no-cache postgresql-client && \
    apk upgrade --no-cache

RUN rm -rf /var/cache/apk/* /tmp/*

COPY scripts/non_live_seeding /app

WORKDIR /app

CMD sh /app/seed_environment.sh
