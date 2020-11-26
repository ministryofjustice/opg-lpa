FROM cypress/included:5.2.0

WORKDIR /app

RUN npm install --save-dev cypress-axe@0.9.1 axe-core cypress-cucumber-preprocessor cosmiconfig
#RUN npm install --save-dev cypress-axe axe-core 

ENV CYPRESS_VIDEO=false

COPY cypress.json .
COPY package.json .
COPY cypress cypress
