FROM composer:2.1.6 AS composer

COPY service-admin/composer.json /app/composer.json
COPY service-admin/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:7.4-fpm-alpine
RUN apk add --upgrade apk-tools
RUN apk upgrade --available
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
COPY service-admin/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/

WORKDIR /app

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
    && php-fpm
