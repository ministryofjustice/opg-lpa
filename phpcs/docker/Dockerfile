FROM composer:2.9.2@sha256:8b4d59fde3bd505c5fef70c9f8d5c05e92af811fed037dad12869b373925ed31 AS composer

FROM php:8.4-cli-alpine3.22@sha256:999f910cc872e4930d2d5c4b91d80ed712e8cf77ba173c729127bfdc5fddfb88

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY phpcs/composer.json /app/composer.json
COPY phpcs/composer.lock /app/composer.lock

RUN composer install --no-interaction --no-progress

ENTRYPOINT ["/app/vendor/bin/phpcbf", "--standard=/app/config/phpcs.xml.dist"]
