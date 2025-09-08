FROM cypress/included:14.5.4@sha256:848fb0d361178e695aa3ebd0f9632f2966232907c0fc02fbd6432e07d4d08d8b

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
