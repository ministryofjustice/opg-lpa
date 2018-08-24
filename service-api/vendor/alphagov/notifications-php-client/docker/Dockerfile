FROM php:5.6-cli

ARG HTTP_PROXY
ARG HTTPS_PROXY
ARG NO_PROXY

ENV DEBIAN_FRONTEND=noninteractive

COPY etc/apt/sources.list /etc/apt/sources.list

RUN \
	echo "Install base packages" \
	&& ([ -z "$HTTP_PROXY" ] || echo "Acquire::http::Proxy \"${HTTP_PROXY}\";" > /etc/apt/apt.conf.d/99HttpProxy) \
	&& apt-get update \
	&& apt-get install -y --no-install-recommends \
		zlib1g-dev \
		make \
		libpcre3-dev \
	&& docker-php-ext-install zip \
	&& curl -x "$HTTP_PROXY" -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
	&& echo "Clean up" \
	&& rm -rf /var/lib/apt/lists/* /tmp/*

WORKDIR /var/project
