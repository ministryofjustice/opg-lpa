FROM composer:2.8.11@sha256:68e926a477000f12e8645e82a020b84904d49071c895c4951551fe80eed5d103 AS composer

FROM php:8.4-cli-alpine3.22@sha256:999f910cc872e4930d2d5c4b91d80ed712e8cf77ba173c729127bfdc5fddfb88

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/

RUN composer require "squizlabs/php_codesniffer:^4.0"

ENTRYPOINT ["./vendor/bin/phpcs", "--standard=/app/config/phpcs.xml.dist"]
