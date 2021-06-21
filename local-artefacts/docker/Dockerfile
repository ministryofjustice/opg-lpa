FROM alpine:latest


RUN apk add ruby
RUN apk add npm

RUN npm install -g grunt-cli \
    && npm install -g grunt-contrib-watch \
    && npm install -g sass

WORKDIR /service-front

CMD ["grunt", "build"]