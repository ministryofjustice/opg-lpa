FROM cypress/included:14.5.2@sha256:e9d61bf5df4c08df6cfa47d3f77dd1fd6debc7a9fa34be158c6ef813dcba3ccd

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
