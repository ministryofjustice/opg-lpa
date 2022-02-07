FROM python:3.8-alpine

RUN pip install awscli psycopg2-binary sqlalchemy alembic

RUN alembic init alembic

COPY service-api-v2/ /app/

WORKDIR /app

CMD alembic upgrade head && python scripts/db/create_apiv2_user.py
