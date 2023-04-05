FROM php:8.1.16-cli-alpine3.17

RUN adduser -D -g '' appuser

RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

RUN apk update \
    && apk add --no-cache openjdk8-jre bash libpng \
    && apk add --no-cache --virtual .build-dependencies autoconf gcc make musl-dev pkgconfig zlib-dev libpng-dev \
    && docker-php-ext-install gd

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

# Install composer dependencies
COPY --chown=root:root service-pdf/composer.json /app/composer.json
COPY --chown=root:root service-pdf/composer.lock /app/composer.lock
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer --version 2.5.5 \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --no-dev --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

# This is version 3.2.1 of this tool, we will need to check this regularly.
# PDFTK does have a community based docker container, but the version is not up to date.
COPY --chown=root:root service-pdf/bin/pdftk /usr/local/bin/pdftk

RUN curl -sS -o /usr/local/bin/pdftk-all.jar https://gitlab.com/pdftk-java/pdftk/-/jobs/812582458/artifacts/raw/build/libs/pdftk-all.jar \
    && chmod 755 /usr/local/bin/pdftk

COPY --chown=root:root shared /shared
COPY --chown=root:root service-pdf /app
COPY --chown=root:root shared/docker/app/app-php.ini /usr/local/etc/php/conf.d/

WORKDIR /app

USER appuser

CMD /app/bin/start.sh
