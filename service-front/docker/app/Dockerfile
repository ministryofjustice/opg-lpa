FROM composer:2.1.11 AS composer

COPY service-front/composer.json /app/composer.json
COPY service-front/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:8.1.1-fpm-alpine
#docker image on hub hasn't been upgraded yet - fixes CVE-2021-36159
RUN apk add --upgrade apk-tools
RUN apk upgrade --available

RUN apk add --update --no-cache icu 

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev \
        && pecl install redis \
        && docker-php-ext-enable redis \
        && docker-php-ext-install intl \
        && docker-php-ext-install bcmath \
        && docker-php-ext-install opcache 

# Default for AWS. Should be set to 1 for local development.
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

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
fi

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

COPY service-front /app
COPY shared/logging /shared/logging
COPY --from=composer /app/vendor /app/vendor
COPY service-front/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-front/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/

WORKDIR /app

CMD php-fpm
