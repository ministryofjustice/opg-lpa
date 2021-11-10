FROM composer:2.1.11 AS composer

COPY service-logging /service-logging
COPY service-front/composer.json /app/composer.json
COPY service-front/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:7.4-fpm-alpine
#docker image on hub hasn't been upgraded yet - fixes CVE-2021-36159
RUN apk add --upgrade apk-tools
RUN apk upgrade --available

RUN apk add --update --no-cache icu

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev \
        && pecl install redis \
        && pecl install xdebug \
        && pecl clear-cache \
        && docker-php-ext-enable redis \
        && docker-php-ext-install intl \
        && docker-php-ext-install bcmath \
        && docker-php-ext-install opcache \
        && apk del .build-dependencies

COPY service-front /app
COPY service-logging /service-logging
COPY --from=composer /app/vendor /app/vendor
COPY service-front/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-front/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/

# Default for AWS. Should be set to 1 for local development.
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

WORKDIR /app

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
    && php-fpm
