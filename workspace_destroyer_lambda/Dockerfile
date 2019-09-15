FROM python:rc-alpine

RUN apk add curl
RUN pip install awscli boto3

RUN mkdir -p /app
WORKDIR /app

COPY app /app
RUN ./install_terraform.sh

ENTRYPOINT [ "sh","./workspace_destroy.sh" ]
