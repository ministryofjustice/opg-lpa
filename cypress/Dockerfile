FROM cypress/included:14.3.3@sha256:168d0d0f92a29a7d5650d2883832958b7d9f9c0a75a67d750041fd9cc7944678

WORKDIR /app

RUN apt-get update && \
  apt-get install -y python3-pip

RUN pip3 install boto3

COPY package.json .

RUN npm install .

ENV CYPRESS_VIDEO=false
ENV CYPRESS_RUN_A11Y_TESTS=true

COPY cypress.config.js .
COPY cypress cypress
COPY tests/python-api-client tests/python-api-client
