FROM php:7.1-cli

ENV DEBIAN_FRONTEND=noninteractive
ENV TERM=linux

RUN apt-get update && \
    apt-get install -y git-core \
    libfreetype6 \
    libfontconfig \
    python \
    libc-client-dev \
    libkrb5-dev \
    openssl \
    libssl-dev
    # libssl-dev
    # phpenmod


RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install -j$(nproc) imap


# The bitbucket location gives occasional "403 Forbidden" errors, breaking the build, so we're using our own source instead
#ADD https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-2.1.1-linux-x86_64.tar.bz2 /tmp/phantomjs.tar.bz2
ADD https://raw.githubusercontent.com/ministryofjustice/opg-phantomjs/master/phantomjs-2.1.1-linux-x86_64.tar.bz2 /tmp/phantomjs.tar.bz2

RUN tar xjfv /tmp/phantomjs.tar.bz2 -C /usr/local/  && \
    ln -sf /usr/local/phantomjs*/bin/phantomjs /usr/local/bin/phantomjs && \
    rm -f /tmp/phantomjs.tar.bz2

RUN git clone https://github.com/n1k0/casperjs.git /usr/local/casperjs && \
    git -C /usr/local/casperjs checkout tags/1.1.3 && \
    ln -sf /usr/local/casperjs/bin/casperjs /usr/local/bin/casperjs

RUN mkdir -p /mnt/test
COPY tests/functional /mnt/test

WORKDIR /mnt/test

