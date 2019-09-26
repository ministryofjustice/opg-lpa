# Casper JS tests

## Initial setup (one time)

### Mac OS

    cd test/casper

Install [Docker Toolbox](https://www.docker.com/products/docker-toolbox)

## Running the tests

Run Docker Machine.

    docker-machine start default

Set up your environment

    eval $(docker-machine env default)

### Running the test suite

Run the following command to run all the tests

    docker run -d -e "CASPER_EMAIL_USER=CASPER_EMAIL_USER" -e "CASPER_EMAIL_PASSWORD=CASPER_EMAIL_PASSWORD" -e "BASE_DOMAIN=BASE_DOMAIN" --name casperjs casperjs:latest

    docker exec casperjs ./start.sh 'tests/'

To run an individual test

	make suite=01-HomePage/04-terms.js test

To run all tests in a directory

    make suite=01-HomePage test

To run the S3Monitor on terminal
aws-vault exec identity -- php S3Monitor.php

To run the casper tests in local environment
```bash
aws-vault exec identity -- docker run -it -v ${PWD}/tests:/mnt/test -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=53-lpa3302add.front.development.lpa.opg.service.justice.gov.uk" --net=host --rm casperjs:latest ./start.sh 'tests/'
```

## Advanced information

When a test suite is run, a service is activated inside the container that monitors the opgcasper@gmail.com account. This allows the test
suites to activate accounts and reset passwords. The email account needs to be setup for IMAP and should have auto-expunge set to Off.

To login to the docker container.

    make shell

Note that once inside anything you do is ephemeral; when you exit and re-enter the state of the container will have been reset.