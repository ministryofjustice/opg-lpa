FROM cypress/included:5.2.0

WORKDIR /app

RUN apt-get update && \
  apt-get install -y php \
      python3-pip 

RUN pip3 install boto3

#RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
#  docker-php-ext-install -j$(nproc) imap

RUN mkdir -p /mnt/test

COPY tests /mnt/test

#RUN php /mnt/test/functional/service/S3Monitor.php &


RUN npm install --save-dev cypress-axe@0.9.1 axe-core cypress-cucumber-preprocessor cosmiconfig

ENV CYPRESS_VIDEO=false

COPY cypress.json .
COPY package.json .
COPY cypress cypress
