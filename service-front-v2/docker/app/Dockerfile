FROM python:3.9-alpine

RUN apk add build-base curl
WORKDIR /makelpa

COPY service-front-v2 /makelpa
RUN pip install -r requirements.txt
RUN ./build.sh

# we run on 8005 so that when running locally we do not clash with dynamodb, which uses 8000
EXPOSE 80
CMD ["gunicorn", "app:app", "--bind", "0.0.0.0:80", "--log-file", "-"]
#CMD ["flask", "run", "--host=0.0.0.0"]
