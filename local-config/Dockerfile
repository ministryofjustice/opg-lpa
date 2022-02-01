FROM python:3.8-alpine

RUN apk update && apk add postgresql-dev 

RUN pip install awscli psycopg2-binary sqlalchemy

ENV WAITFORIT_VERSION="v2.4.1"
RUN wget -q -O /usr/local/bin/waitforit https://github.com/maxcnunes/waitforit/releases/download/$WAITFORIT_VERSION/waitforit-linux_amd64 \
  && chmod +x /usr/local/bin/waitforit

# COPY commands should mirror volumes set up in docker-compose.yml
COPY ./local-config/start.sh /app/
COPY ./scripts/db/create_feedback_table.py /app/

WORKDIR /app

CMD chmod +x /app/*.sh \
  && /app/start.sh
