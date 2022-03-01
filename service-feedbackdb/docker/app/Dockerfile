FROM python:3.8-alpine

# security fix for expat
RUN apk upgrade expat

RUN pip install awscli psycopg2-binary sqlalchemy alembic

RUN alembic init alembic

COPY service-feedback-api/ /app/

WORKDIR /app

CMD alembic upgrade head && python scripts/db/create_feedback_user.py
