FROM registry.service.opg.digital/opg-php-fpm-1604

# We need version 1.2 of the mongo extension
RUN apt remove -y php-mongodb

RUN apt-get install -y php-dev pkg-config

RUN pecl install mongodb-1.2.9 && \
    echo "extension=mongodb.so" > /etc/php/7.0/mods-available/mongodb.ini && \
    phpenmod mongodb

RUN cd /tmp && curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer