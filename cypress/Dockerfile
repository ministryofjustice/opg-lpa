FROM cypress/included:14.5.3@sha256:c83bafd958f31f12add02cf7df35bf607390951ca6d80103fc7ac2498720cc16

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
