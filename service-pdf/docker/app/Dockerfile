FROM composer:2.10.1@sha256:7725eb4545c438629ae8bde3ef0bb9a5038ef566126ad878442a69007242d267 AS composer

FROM pdftk/pdftk:3.3.3@sha256:01c1bc85f760226bc5d0dc2567c7f4ec6444f070212be610fe803cd5ce160872 AS pdftk

FROM php:8.4.19-fpm-alpine3.23@sha256:38c3275e8d8fe1b395dd81dccdbdbec6b0fa1fa318e2391c4ad98eae3b6dc0e3 AS base

RUN adduser -D -g '' appuser && \
  chown -R appuser:appuser /tmp

RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

RUN apk update \
    && apk add --no-cache openjdk8-jre libpng \
    && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS autoconf gcc make musl-dev pkgconfig zlib-dev libpng-dev linux-headers \
    && apk add --upgrade expat \
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
        touch /tmp/xdebug.log ; \
        chown appuser:appuser /tmp/xdebug.log ; \
    fi

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

USER appuser

VOLUME [ "/tmp" ]

CMD ["/app/bin/start.sh"]

FROM base AS test-runner

USER "root"
RUN chown -R appuser:appuser /app/vendor

COPY --chown=root:root service-pdf/composer.json /app/composer.json
COPY --chown=root:root service-pdf/composer.lock /app/composer.lock
COPY --from=composer /usr/bin/composer /usr/bin/

RUN composer check-platform-reqs -d /app \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer

USER appuser

COPY --chown=root:root service-pdf/phpunit.xml /app/phpunit.xml
COPY --chown=root:root service-pdf/psalm.xml /app/psalm.xml

FROM base AS production

USER root
RUN rm -rf /app/test-tools
RUN rm -rf /app/tests
USER appuser
