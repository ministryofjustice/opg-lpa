FROM cypress/included:5.2.0

WORKDIR /app

RUN apt-get update && \
  apt-get install -y php \
      python3-pip 

RUN pip3 install boto3

RUN mkdir -p /mnt/test

COPY cypress/activation_emails /mnt/test/activation_emails

RUN npm install --save-dev cypress-axe@0.9.1 axe-core cypress-cucumber-preprocessor cosmiconfig 

# unset CI which gets set in Cypress's dockerfile
ENV CI=
ENV CYPRESS_VIDEO=false
ENV CYPRESS_RUN_A11Y_TESTS=true

COPY cypress.json .
COPY package.json .
COPY cypress cypress
