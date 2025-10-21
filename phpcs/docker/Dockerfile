FROM composer:2.8.12@sha256:3ca62a8176c743eebef305ac2b93094a733dd5a34b5c1e3d3cf6cbbbd0792649 AS composer

FROM php:8.4-cli-alpine3.22@sha256:999f910cc872e4930d2d5c4b91d80ed712e8cf77ba173c729127bfdc5fddfb88

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/

RUN composer require "squizlabs/php_codesniffer:^4.0"

ENTRYPOINT ["./vendor/bin/phpcs", "--standard=/app/config/phpcs.xml.dist"]
