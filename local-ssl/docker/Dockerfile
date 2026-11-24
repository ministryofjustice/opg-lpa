FROM nginx:1.31.9-alpine3.24@sha256:fb4658584bd9a916ffb6f6e365f97c25cfdb2923801f551ea08356e695f14ff7

RUN apk upgrade --no-cache

# the nginx entrypoint renders templates with envsubst; write the result over
# nginx.conf. This config is tweaked to mock out an X-Amzn-Trace-Id header
ENV NGINX_ENVSUBST_OUTPUT_DIR=/etc/nginx \
    TARGET_SCHEME=http \
    CLIENT_MAX_BODY_SIZE=20M \
    SSL_PORT=443

COPY ./local-ssl/docker/nginx.conf.template /etc/nginx/templates/nginx.conf.template
