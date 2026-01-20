FROM composer:2.9.3@sha256:ce78434db8122b0c3b0ec2056da0072551780d60e864943008c3f5a785db0900 AS composer

FROM php:8.4-cli-alpine3.22@sha256:999f910cc872e4930d2d5c4b91d80ed712e8cf77ba173c729127bfdc5fddfb88

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY phpcs/composer.json /app/composer.json
COPY phpcs/composer.lock /app/composer.lock

RUN composer install --no-interaction --no-progress

ENTRYPOINT ["/app/vendor/bin/phpcbf", "--standard=/app/config/phpcs.xml.dist"]
