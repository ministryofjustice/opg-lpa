FROM composer AS composer

COPY service-admin /app

RUN composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader

FROM php:7-fpm-alpine

RUN apk add --update --no-cache icu

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev \
        && pecl install xdebug \
        && pecl clear-cache \
        && docker-php-ext-install intl \
        && docker-php-ext-install bcmath \
        && apk del .build-dependencies

COPY service-admin /app
COPY --from=composer /app/vendor /app/vendor
COPY service-admin/docker/app/app-php.ini /usr/local/etc/php/conf.d/

WORKDIR /app

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
    && php-fpm

