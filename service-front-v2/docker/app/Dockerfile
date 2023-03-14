FROM python:3.11-alpine

RUN apk add --no-cache build-base curl && \
    apk upgrade --no-cache

WORKDIR /makelpa

COPY service-front-v2 .
RUN pip install -r requirements.txt
RUN ./build.sh

EXPOSE 8005
CMD ["gunicorn", "app:app", "--bind", "0.0.0.0:8005", "--log-file", "-"]
