FROM alpine:latest

RUN apk add ruby npm

WORKDIR /app

COPY ./node-build-assets/docker/start.sh /app/
COPY ./service-front/package*.json /app/

RUN npm ci && \
    chmod +x /app/start.sh

CMD ["/app/start.sh"]
