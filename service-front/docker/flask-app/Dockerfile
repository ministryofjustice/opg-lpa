FROM python:3.9-alpine
#FROM tiangolo/uwsgi-nginx-flask:python3.8-alpine

RUN apk add build-base curl
WORKDIR /makelpa

COPY service-front/govuk-frontend-jinja-makelpa /makelpa
RUN pip install -r requirements.txt
RUN ./build.sh

#ENV FLASK_APP app.py
#RUN flask --help

EXPOSE 5000
#CMD ["gunicorn", "app:app", "--bind", ":5000", "--log-file", "-"]
CMD ["flask", "run", "--host=0.0.0.0"]
