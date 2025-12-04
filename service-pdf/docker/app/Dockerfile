FROM composer:2.9.2@sha256:1997db9e43fde2a5a20f32fcfd50f26f698d951b3787334ebd6493d0562de97f AS composer

FROM pdftk/pdftk:3.3.3@sha256:80d553d749d2e46904323b15bed7e4dbd08f17573bb8470e51285ae363cc4e3c AS pdftk

FROM php:8.4.13-fpm-alpine3.22@sha256:aee4a044beefc7d88d0419aee8825491e02391f37707b8fc0d8edba1a29c1165

RUN adduser -D -g '' appuser

RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

RUN apk update \
    && apk add --no-cache openjdk8-jre libpng \
    && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS autoconf gcc make musl-dev pkgconfig zlib-dev libpng-dev linux-headers \
    && docker-php-ext-install gd 

# only install poppler-utils and imagemagick, used by visual diff tests, if VISUAL_TESTS is set to true in the build stage
ARG VISUAL_TESTS=false
RUN if [ "$VISUAL_TESTS" = "true" ] ; then apk add --no-cache poppler-utils imagemagick imagemagick-dev \
    && pecl install imagick && docker-php-ext-enable imagick; fi

# Install composer dependencies
COPY --chown=root:root service-pdf/composer.json /app/composer.json
COPY --chown=root:root service-pdf/composer.lock /app/composer.lock
COPY --from=composer /usr/bin/composer /usr/bin/
RUN composer check-platform-reqs --no-dev -d /app \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --no-dev --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer


COPY --from=pdftk /usr/bin/pdftk /usr/bin/
RUN mkdir /usr/share/java
COPY --from=pdftk /usr/share/java/pdftk.jar /usr/share/java

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
