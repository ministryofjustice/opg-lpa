FROM composer:2.1.3 AS composer

COPY service-api/composer.json /app/composer.json
COPY service-api/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:7.4.20-fpm-alpine3.14

# Postgres lib needs to remain in the container
RUN apk add --update --no-cache postgresql-libs

# Postgres dev lib is temporary
RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS postgresql-dev \
  && pecl install xdebug \
  && pecl clear-cache \
  && docker-php-ext-install pgsql pdo_pgsql \
  && docker-php-ext-install bcmath \
  && docker-php-ext-install opcache \
  && apk del .build-dependencies

COPY service-api /app
COPY --from=composer /app/vendor /app/vendor
COPY service-api/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-api/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/

# Default for AWS. Should be set to 1 for local development.
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

WORKDIR /app

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
  && chmod +x /app/docker/app/db-migrations.sh && /app/docker/app/db-migrations.sh \
  && php-fpm
