FROM alpine:3.24.1@sha256:28bd5fe8b56d1bd048e5babf5b10710ebe0bae67db86916198a6eec434943f8b

RUN apk add --update --no-cache postgresql-client && \
    apk upgrade --no-cache

RUN rm -rf /var/cache/apk/* /tmp/*

RUN adduser -D -g '' appuser

COPY scripts/non_live_seeding /app

RUN chown -R appuser:appuser /app

WORKDIR /app

USER appuser

CMD ["/app/seed_environment.sh"]
