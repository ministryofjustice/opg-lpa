FROM composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS composer

FROM php:8.4-cli-alpine3.23@sha256:05aa2edde7c3f828fef15b2508edfbac334dda7e1070de56d86a8b2c40b116a6

RUN adduser -D -g '' appuser
COPY --chown=root:root shared/docker/php-mitigations.ini /usr/local/etc/php/conf.d/

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/
COPY phpcs/composer.json /app/composer.json
COPY phpcs/composer.lock /app/composer.lock

RUN composer install --no-interaction --no-progress

RUN find vendor -name composer.lock -delete

RUN mkdir -p /app/output && chmod a+w /app/output

USER appuser

ENTRYPOINT ["/app/vendor/bin/phpcbf", "--standard=/app/config/phpcs.xml.dist"]
