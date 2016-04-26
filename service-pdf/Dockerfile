FROM registry.service.dsd.io/opguk/base:latest

RUN groupadd supervisor

RUN apt-get update && apt-get install -y \
    php5-cli php5-dev pdftk php5-mcrypt

RUN php5enmod mcrypt

RUN pecl install proctitle-0.1.2 && \
    echo "extension=proctitle.so" > /etc/php5/mods-available/proctitle.ini && \
    php5enmod proctitle

ADD . /app
RUN mkdir -p /srv/opg-lpa-pdf2/application && \
    mkdir /srv/opg-lpa-pdf2/application/releases && \
    mkdir /srv/opg-lpa-pdf2/application/shared && \
    mkdir /srv/opg-lpa-pdf2/application/shared/log && \
    mkdir /srv/opg-lpa-pdf2/application/shared/pids && \
    mkdir /srv/opg-lpa-pdf2/application/shared/system && \
    mkdir /srv/opg-lpa-pdf2/application/shared/tmp && \
    mkdir /srv/opg-lpa-pdf2/application/shared/session && \
    chown -R app:app /srv/opg-lpa-pdf2/application && \
    chmod -R 755 /srv/opg-lpa-pdf2/application && \
    ln -s /app /srv/opg-lpa-pdf2/application/current

RUN mkdir /etc/service/opg-lpa-pdf2/
ADD docker/confd /etc/confd

ENV OPG_SERVICE pdf
