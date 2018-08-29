FROM registry.service.opg.digital/opg-php-fpm-71-ppa-1604

RUN apt update && apt install -y \
    php7.1-bcmath

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

ENV OPG_SERVICE api
