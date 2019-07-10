FROM composer AS composer

COPY opg-lpa-api /app

RUN composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader

FROM php:7-fpm-alpine

# Postgres lib needs to remain in the container
RUN apk add --update --no-cache postgresql-libs

# Postgres dev lib is temporary
RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS postgresql-dev \
        && pecl install xdebug \
        && pecl clear-cache \
        && docker-php-ext-install pgsql pdo_pgsql \
        && docker-php-ext-install bcmath \
        && apk del .build-dependencies

COPY opg-lpa-api /app
COPY --from=composer /app/vendor /app/vendor
COPY opg-lpa-api/docker/app/app-php.ini /usr/local/etc/php/conf.d/

WORKDIR /app

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
    && php-fpm
