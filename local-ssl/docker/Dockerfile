FROM nginx:1.31.8-alpine3.24@sha256:a3554c0d0b48177b1413c26e01f695334428020d9df01243fbae3e11cfd17417

RUN apk upgrade --no-cache

# the nginx entrypoint renders templates with envsubst; write the result over
# nginx.conf. This config is tweaked to mock out an X-Amzn-Trace-Id header
ENV NGINX_ENVSUBST_OUTPUT_DIR=/etc/nginx \
    TARGET_SCHEME=http \
    CLIENT_MAX_BODY_SIZE=20M \
    SSL_PORT=443

COPY ./local-ssl/docker/nginx.conf.template /etc/nginx/templates/nginx.conf.template
