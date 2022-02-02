FROM python:3.8-alpine

RUN pip install awscli psycopg2-binary sqlalchemy

#RUN apk add --update --no-cache postgresql-client

#RUN rm -rf /var/cache/apk/* /tmp/*

COPY scripts/db /app

WORKDIR /app

CMD python create_feedback_table.py
