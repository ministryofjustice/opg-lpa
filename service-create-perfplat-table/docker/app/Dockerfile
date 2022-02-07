FROM python:3.8-alpine

RUN pip install awscli psycopg2-binary sqlalchemy alembic

RUN alembic init alembic

COPY service-api-v2/ /app/

COPY scripts/db /app

WORKDIR /app

CMD alembic upgrade head
#CMD alembic upgrade head && python create_feedback_table.py
