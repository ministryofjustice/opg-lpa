FROM composer:2.8.12@sha256:5248900ab8b5f7f880c2d62180e40960cd87f60149ec9a1abfd62ac72a02577c AS composer

FROM php:8.4.13-fpm-alpine3.22@sha256:aee4a044beefc7d88d0419aee8825491e02391f37707b8fc0d8edba1a29c1165

RUN adduser -D -g '' appuser

RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

RUN apk update \
    && apk add --no-cache openjdk8-jre libpng \
    && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS autoconf gcc make musl-dev pkgconfig zlib-dev libpng-dev linux-headers\
    && docker-php-ext-install gd

# Install composer dependencies
COPY --chown=root:root service-pdf/composer.json /app/composer.json
COPY --chown=root:root service-pdf/composer.lock /app/composer.lock
COPY --from=composer /usr/bin/composer /usr/bin/
RUN composer check-platform-reqs --no-dev -d /app \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --no-dev --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer


# This is version 3.2.1 of this tool, we will need to check this regularly.
# PDFTK does have a community based docker container, but the version is not up to date.
COPY --chown=root:root service-pdf/bin/pdftk /usr/local/bin/pdftk

RUN curl -sS -o /usr/local/bin/pdftk-all.jar https://gitlab.com/pdftk-java/pdftk/-/jobs/812582458/artifacts/raw/build/libs/pdftk-all.jar \
    && chmod 755 /usr/local/bin/pdftk

COPY --chown=root:root shared /shared
COPY --chown=root:root service-pdf /app
COPY --chown=root:root shared/docker/app/app-php.ini /usr/local/etc/php/conf.d/

WORKDIR /app

# Enable debug if needed. Should only be used locally.
ARG ENABLE_XDEBUG=0
RUN if [ "$ENABLE_XDEBUG" = "1" ] ; then \
        pecl install xdebug ; \
        echo "xdebug.mode = develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
        echo "xdebug.discover_client_host = true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
        echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
        echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
        echo "xdebug.log = /tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
        echo "xdebug.idekey = PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini ; \
        docker-php-ext-enable xdebug ; \
    fi

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

USER appuser

CMD ["/app/bin/start.sh"]
