FROM composer:2.10.1@sha256:7725eb4545c438629ae8bde3ef0bb9a5038ef566126ad878442a69007242d267 AS composer

FROM php:8.4-cli-alpine3.22@sha256:999f910cc872e4930d2d5c4b91d80ed712e8cf77ba173c729127bfdc5fddfb88

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY phpcs/composer.json /app/composer.json
COPY phpcs/composer.lock /app/composer.lock

RUN composer install --no-interaction --no-progress

ENTRYPOINT ["/app/vendor/bin/phpcbf", "--standard=/app/config/phpcs.xml.dist"]
