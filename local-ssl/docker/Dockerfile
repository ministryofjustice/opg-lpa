FROM nginx:1.31.7-alpine3.24@sha256:7ddef4fb1b16816185c5e3ca4c76086e2db0d37f2e9eee57b9a3fb5182cca562

RUN apk upgrade --no-cache

RUN apk update && apk add --upgrade "ca-certificates==20261020-r0"

# the nginx entrypoint renders templates with envsubst; write the result over
# nginx.conf. This config is tweaked to mock out an X-Amzn-Trace-Id header
ENV NGINX_ENVSUBST_OUTPUT_DIR=/etc/nginx \
    TARGET_SCHEME=http \
    CLIENT_MAX_BODY_SIZE=20M \
    SSL_PORT=443

COPY ./local-ssl/docker/nginx.conf.template /etc/nginx/templates/nginx.conf.template
