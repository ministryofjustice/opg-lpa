FROM php:8.1-fpm-alpine3.15

RUN adduser -D -g '' appuser
RUN apk add --upgrade --no-cache apk-tools
RUN apk upgrade --no-cache

# The --virtual flag puts build dependencies into their own
# directory to make them easier to remove en masse
RUN apk add --no-cache icu fcgi \
    && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev \
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

# Install composer dependencies
COPY --chown=root:root service-front/composer.json /app/composer.json
COPY --chown=root:root service-front/composer.lock /app/composer.lock
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer --version 2.5.1 \
    && composer install --prefer-dist --no-interaction --no-cache --no-scripts --no-dev --optimize-autoloader -d /app \
    && chown -R root:root /app/vendor \
    && rm /app/composer.json \
    && rm /usr/bin/composer

# Clean up build dependencies, but only after everything else we need has been installed
RUN apk del .build-dependencies

# Core files for the application
COPY --chown=root:root service-front/assets /app/assets
COPY --chown=root:root service-front/config /app/config
COPY --chown=root:root service-front/content /app/content
COPY --chown=root:root service-front/module /app/module
COPY --chown=root:root service-front/public /app/public

# Shared code
COPY --chown=root:root shared /shared

# PHP config files
COPY --chown=root:root service-front/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY --chown=root:root service-front/docker/app/php-fpm-logging.conf /usr/local/etc/php-fpm.d/

COPY --chown=root:root scripts/containers/health-check.sh /usr/local/bin/health-check.sh

RUN chmod +x /usr/local/bin/health-check.sh

WORKDIR /app

USER appuser

CMD php-fpm
