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

    make testall

To run an individual test

	make suite=01-HomePage/04-terms.js test

To run all tests in a directory

    make suite=01-HomePage test

## Advanced information

When a test suite is run, a service is activated inside the container that monitors the opgcasper@gmail.com account. This allows the test
suites to activate accounts and reset passwords. The email account needs to be setup for IMAP and should have auto-expunge set to Off.

To login to the docker container.

    make shell

Note that once inside anything you do is ephemeral; when you exit and re-enter the state of the container will have been reset.
