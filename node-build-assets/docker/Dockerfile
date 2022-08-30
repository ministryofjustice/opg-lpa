FROM alpine:latest

RUN apk add ruby npm

WORKDIR /app

COPY ./node-build-assets/docker/start.sh /app/
RUN npm install -g grunt-cli grunt-contrib-watch sass && \
    chmod +x /app/start.sh

CMD ["/app/start.sh"]
