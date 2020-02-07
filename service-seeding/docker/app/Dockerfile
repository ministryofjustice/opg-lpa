FROM python:alpine3.10

RUN apk add --update --no-cache postgresql-client

RUN pip install --upgrade pip \
  && pip install --no-cache --upgrade \
  awscli

# RUN apk del --purge .build-dependencies
RUN rm -rf /var/cache/apk/* /tmp/*

COPY scripts/non_live_seeding /app

WORKDIR /app

CMD chmod +x seed_environment.sh && ./seed_environment.sh
