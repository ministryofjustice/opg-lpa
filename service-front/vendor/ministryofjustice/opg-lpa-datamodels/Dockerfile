FROM registry.service.opg.digital/opguk/php-fpm

RUN php5enmod mcrypt

RUN apt-get update && apt-get install -y \
    php5-curl php-pear php5-dev

RUN apt-get install -y pkg-config

RUN pecl install mongodb-1.2.9 && \
    echo "extension=mongodb.so" > /etc/php5/mods-available/mongodb.ini && \
    php5enmod mongodb

RUN  cd /tmp && curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer