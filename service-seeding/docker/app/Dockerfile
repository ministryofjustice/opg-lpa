FROM alpine:3.24.1@sha256:bec4ccd3817e7c824eb0388971a0b83fab111d586285511ba0266b77e8dc65a9

RUN apk add --update --no-cache postgresql-client && \
    apk upgrade --no-cache

RUN rm -rf /var/cache/apk/* /tmp/*

COPY scripts/non_live_seeding /app

WORKDIR /app

CMD ["/app/seed_environment.sh"]
