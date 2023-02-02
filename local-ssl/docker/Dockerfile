FROM fsouza/docker-ssl-proxy

# replace the nginx config from docker-ssl-proxy with a version tweaked
# to mock out an X-Amzn-Trace-Id header
COPY ./local-ssl/docker/nginx.conf.template /

RUN apk upgrade --no-cache
