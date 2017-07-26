FROM registry.service.opg.digital/opg-php-fpm-1604

# Should be in the base image
RUN apt install -y php-xdebug php-dev

# We need version 1.2 of the mongo extension
RUN apt remove -y php-mongodb

RUN apt-get install -y pkg-config

RUN pecl install mongodb-1.2.9 && \
    echo "extension=mongodb.so" > /etc/php/7.0/mods-available/mongodb.ini && \
    phpenmod mongodb

RUN groupadd webservice && \
    groupadd supervisor

# Add application logging config(s)
ADD docker/beaver.d /etc/beaver.d

ADD . /app
RUN mkdir -p /srv/opg-lpa-api2/application && \
    mkdir /srv/opg-lpa-api2/application/releases && \
    chown -R app:app /srv/opg-lpa-api2/application && \
    chmod -R 755 /srv/opg-lpa-api2/application && \
    ln -s /app /srv/opg-lpa-api2/application/current

ADD docker/my_init/* /etc/my_init.d/
ADD docker/certificates/* /usr/local/share/ca-certificates/

ADD docker/bin/update-ca-certificates /usr/sbin/update-ca-certificates
RUN chmod 755 /usr/sbin/update-ca-certificates; sync; /usr/sbin/update-ca-certificates --verbose

ENV OPG_SERVICE api
