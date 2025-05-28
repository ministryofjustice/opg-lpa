FROM fsouza/docker-ssl-proxy:1.6@sha256:47d7505e2524e7396e92b6a05188d375b4d7d8bd22b2a758fd9432529cd2e84a

# replace the nginx config from docker-ssl-proxy with a version tweaked
# to mock out an X-Amzn-Trace-Id header
COPY ./local-ssl/docker/nginx.conf.template /

RUN apk upgrade --no-cache
