FROM composer AS composer

COPY opg-lpa-pdf /app

RUN composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader

FROM php:7-cli

# mkdir below fixes a known bug with openjdk https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=863199
RUN apt-get -y update \
    && mkdir -p /usr/share/man/man1 \
    && apt-get -y install pdftk \
    && apt-get -y clean

COPY opg-lpa-pdf /app
COPY --from=composer /app/vendor /app/vendor

WORKDIR /app
