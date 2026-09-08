FROM composer:2.10.2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040 AS composer

FROM php:8.4-cli-alpine3.23@sha256:d9e834cf026453eb88844f6a2cd864131eb134d3445ea3b67c4a22ac60361954

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
