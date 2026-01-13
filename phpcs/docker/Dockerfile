FROM composer:2.9.3@sha256:631d7c9ef9cca5fd2f87f691bb9175c89df3c69fbced73148314c11df270a108 AS composer

FROM php:8.4-cli-alpine3.22@sha256:999f910cc872e4930d2d5c4b91d80ed712e8cf77ba173c729127bfdc5fddfb88

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY phpcs/composer.json /app/composer.json
COPY phpcs/composer.lock /app/composer.lock

RUN composer install --no-interaction --no-progress

ENTRYPOINT ["/app/vendor/bin/phpcbf", "--standard=/app/config/phpcs.xml.dist"]
