FROM composer:2.1.11 AS composer

COPY service-pdf/composer.json /app/composer.json
COPY service-pdf/composer.lock /app/composer.lock

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:8.0-cli-alpine

RUN apk add --upgrade apk-tools
RUN apk upgrade --available

# security fix for expat
RUN apk upgrade expat

RUN apk update \
    && apk add --no-cache openjdk8-jre gcc make musl-dev pkgconfig bash\
    && apk add --no-cache --update --virtual buildDeps autoconf 

# Enable debug if needed. Should only be used locally.
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
RUN apk del buildDeps

WORKDIR /usr/local/bin/
# This is version 3.2.1 of this tool, we will need to check this regularly.
# PDFTK does have a community based docker container, but the version is not up to date.

RUN wget https://gitlab.com/pdftk-java/pdftk/-/jobs/812582458/artifacts/raw/build/libs/pdftk-all.jar

COPY service-pdf/bin/pdftk pdftk
RUN chmod +x pdftk

COPY shared/logging /shared/logging
COPY service-pdf /app
COPY --from=composer /app/vendor /app/vendor

WORKDIR /app

CMD /app/bin/start.sh
