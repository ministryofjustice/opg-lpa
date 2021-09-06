FROM python:3.9-alpine

RUN apk add build-base curl
WORKDIR /makelpa

COPY service-front-v2 /makelpa
RUN pip install -r requirements.txt
RUN ./build.sh

EXPOSE 8000
CMD ["gunicorn", "app:app", "--bind", "0.0.0.0:8000", "--log-file", "-"]
#CMD ["flask", "run", "--host=0.0.0.0"]
