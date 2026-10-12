FROM alpine:3.24@sha256:294b683cb724975bec92580e1e685676bd4b50bda910ddb8c51d4cabeaec77e6

RUN apk add --update --no-cache postgresql-client && \
    apk upgrade --no-cache

# TODO Remove this once alpine upgrade available as it patches a vulnerability
RUN apk update && apk add --upgrade "zlib==1.3.2-r1"

RUN rm -rf /var/cache/apk/* /tmp/*

RUN adduser -D -g '' appuser

COPY scripts/non_live_seeding /app

RUN chown -R appuser:appuser /app

WORKDIR /app

USER appuser

CMD ["/app/seed_environment.sh"]
