FROM composer:2.1.11 AS composer

COPY service-admin/composer.json /app/composer.json
COPY service-admin/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:8.1.2-fpm-alpine
RUN apk add --upgrade apk-tools
RUN apk upgrade --available
RUN apk add --update --no-cache icu

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev \
        && docker-php-ext-install intl \
        && docker-php-ext-install bcmath 

# Enable debug if needed. Should only be used locally
ARG ENABLE_XDEBUG=0
RUN if [[ $ENABLE_XDEBUG = 1 ]] ; then \
      pecl install xdebug ; \
      docker-php-ext-enable xdebug ; \
      echo "xdebug.mode = develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.discover_client_host = true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
      echo "xdebug.idekey = PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
    fi ;

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

COPY shared/logging /shared/logging
COPY service-admin /app
COPY --from=composer /app/vendor /app/vendor
COPY service-admin/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-admin/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/

WORKDIR /app

CMD php-fpm
