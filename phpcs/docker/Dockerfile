FROM composer:2.10.2@sha256:d020706319701a44468968321dccd0fce6620190159a7a9ec195d78e6e971c71 AS composer

FROM php:8.4-cli-alpine3.23@sha256:18517a48b5dec9be4a2c9b61fc9d7b99b8ca9c6897a378f1cb22fd88a262a2e8

RUN adduser -D -g '' appuser

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY phpcs/composer.json /app/composer.json
COPY phpcs/composer.lock /app/composer.lock

RUN composer install --no-interaction --no-progress

RUN find vendor -name composer.lock -delete

RUN mkdir -p /app/output && chmod a+w /app/output

USER appuser

ENTRYPOINT ["/app/vendor/bin/phpcbf", "--standard=/app/config/phpcs.xml.dist"]
