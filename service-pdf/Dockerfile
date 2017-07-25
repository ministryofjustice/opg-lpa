FROM registry.service.opg.digital/opguk/base:0.1.213

RUN groupadd supervisor

RUN apt-get update && apt-get install -y \
    php5-cli php5-dev pdftk php5-mcrypt php5-curl

RUN php5enmod mcrypt

RUN pecl install proctitle-0.1.2 && \
    echo "extension=proctitle.so" > /etc/php5/mods-available/proctitle.ini && \
    php5enmod proctitle

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
