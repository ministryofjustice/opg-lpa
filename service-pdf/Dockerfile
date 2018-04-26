FROM registry.service.opg.digital/opg-php-fpm-71-ppa-1604

RUN groupadd supervisor

RUN apt-get update && apt-get install -y \
    make php7.1-cli php7.1-dev pdftk php7.1-mcrypt php7.1-curl php-pear

RUN pecl install proctitle-0.1.2 && \
    echo "extension=proctitle.so" > /etc/php/7.0/mods-available/proctitle.ini && \
    phpenmod proctitle

# Add application logging config(s)
ADD docker/beaver.d /etc/beaver.d

ADD . /app
RUN mkdir -p /srv/opg-lpa-pdf2/application && \
    mkdir /srv/opg-lpa-pdf2/application/releases && \
    chown -R app:app /srv/opg-lpa-pdf2/application && \
    chmod -R 755 /srv/opg-lpa-pdf2/application && \
    ln -s /app /srv/opg-lpa-pdf2/application/current

RUN mkdir /etc/service/opg-lpa-pdf2/
ADD docker/confd /etc/confd

ENV OPG_SERVICE pdf
