FROM alpine:3.24@sha256:be2656061b536c1883c4034790e21d0a09c5aaaf51968b89368a3c0371ea2c9a

RUN apk add --update --no-cache postgresql-client && \
    apk upgrade --no-cache

# TODO Remove this once alpine upgrade available as it patches a vulnerability
RUN apk update && apk add --upgrade "zlib==1.3.2-r1" "ca-certificates-bundle==20261020-r0"

RUN rm -rf /var/cache/apk/* /tmp/*

RUN adduser -D -g '' appuser

COPY scripts/non_live_seeding /app

RUN chown -R appuser:appuser /app

WORKDIR /app

USER appuser

CMD ["/app/seed_environment.sh"]
