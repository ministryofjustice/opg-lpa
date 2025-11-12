
# Troubleshooting Container Images <!-- omit in toc -->

- [Introduction](#introduction)
- [Prerequisites](#prerequisites)
- [Scenarios](#scenarios)
- [Build dockerfile locally](#build-dockerfile-locally)
- [Pull an existing ECR image](#pull-an-existing-ecr-image)
- [Run PHPUnit directly on the container](#run-phpunit-directly-on-the-container)
- [Set up individual docker run configuration for PHPStorm](#set-up-individual-docker-run-configuration-for-phpstorm)
- [Setting up debugging of PHPUnit tests in PHPStorm](#setting-up-debugging-of-phpunit-tests-in-phpstorm)
  - [Create the configuration](#create-the-configuration)
  - [Configure the remote interpreter](#configure-the-remote-interpreter)
  - [Configure the remote interpreter for the test framework](#configure-the-remote-interpreter-for-the-test-framework)
- [Testing service-api manually with cURL](#testing-service-api-manually-with-curl)

## Introduction

Sometimes, when GitHub actions builds docker images,it may produce results that are different or unexpected to what you are seeing on your local machine. Reasons this may happen include:

- Issues with composer.lock, either changed or not present.
- Docker images e.g. php, python are pulled from the `:latest` tag and a breaking update happens to the image.
- Issues with `npm, grunt or gulp` for example.

You may want to debug the images generated in more detail, perhaps even go through the approach that GitHub actions uses, but locally. This doc helps you do that.

**Note:** this guide is not exhaustive, so please add items for new scenarios.

## Prerequisites

you will need:

- aws-vault.
- profile in the `~/.aws/config/` that is a dev operator.
- AWS CLI
- Docker Desktop for Mac
- PHPStorm

please see [Setting up AWS Credentials](../setting_up_aws_credentials/setting_up_credentials.md) for details of how to set up aws cli and aws-vault on your local machine.

## Scenarios

Here's a number of things that will help when debugging a container issue:

- If an issue was spotted in PHPUnit tests in GitHub actions, you could [Build the dockerfile locally](#build-dockerfile-locally)
- If an issue was spotted after the container was pushed, and later spun up you can also [Pull the existing ECR image](#pull-an-existing-ecr-image)
- For a quick dignostic of the unit tests on the container: [Run PHPUnit directly on the container](#run-phpunit-directly-on-the-container)
- To run the container in PHP Storm: [Set up individual docker run configuration for PHPStorm](#set-up-individual-docker-run-configuration-for-phpstorm)
- To deep debug the tests on container: [Setting up debugging of PHPUnit tests in PHPStorm](#setting-up-debugging-of-phpunit-tests-in-phpstorm)
- To check whether the API is working correctly (in isolation from the front ends): [Testing service-api manually with cURL](#testing-service-api-manually-with-curl)

Examples below cover api container, but equally apply for front, admin and pdf containers too.

## Build dockerfile locally

Build the dockerfile for container image you wish to debug - it's best to keep names as per how GitHub actions produces, if you want to mirror the CI process. e.g

 ``` bash
docker compose build --no-cache api-app
 ```

## Pull an existing ECR image

Login to ECR and pull the container using the following commands. You can also replace the `:latest` with a specific tag; e.g. `main-<semver-tag>` where `semver-tag` is the semantic-versioned tag on the commit that generated the build, so you can pinpoint a specific image:

``` bash
aws-vault exec moj-lpa-dev --  aws ecr get-login-password --region eu-west-1 | docker login --username AWS --password-stdin 311462405659.dkr.ecr.eu-west-1.amazonaws.com
docker pull 311462405659.dkr.ecr.eu-west-1.amazonaws.com/online-lpa/api_app:latest
```

## To run the unit tests on your locally built image:

```bash
docker compose run -it --no-deps api-app vendor/bin/phpunit
```
To run on a specific version of the service, check out the associated commit/tag first.

## Testing service-api manually with cURL

If you are having issues with the application, you can check whether the API part of it is responding correctly by manually invoking it with cURL.

When running under docker compose, the API is on http://localhost:7001/

Requests should have the following headers set to mimic requests from the admin app (the main consumer of the API):

* Accept: application/json
* Content-Type: application/json
* User-Agent: LPA-ADMIN

To authenticate to the API:

* POST username, password to /v2/authenticate

For example:

```
$ curl -X POST -H "Accept: application/json" -H "Content-Type: application/json" -H "User-Agent: LPA-ADMIN" \
  -d '{"username": "seeded_test_user@digital.justice.gov.uk", "password": "Pass1234"}' \
  "http://localhost:7001/v2/authenticate"

{"userId":"082347fe0f7da026fa6187fc00b05c55","username":"seeded_test_user@digital.justice.gov.uk",
"last_login":"2020-01-21T15:16:02+0000","inactivityFlagsCleared":false,
"token":"yIU0G8NiTesl4hev0wXIQHpeipcdiAIiMvRpT0hZ2rl","expiresIn":4500,"expiresAt":"2020-12-03T12:16:34+0000"}
```

The `token` returned here can be sent with subsequent requests to other parts of the API:

```
$ curl -H "Accept: application/json" -H "User-Agent: LPA-ADMIN" \
  -H "Token: yIU0G8NiTesl4hev0wXIQHpeipcdiAIiMvRpT0hZ2rl" \
  "http://localhost:7001/v2/users/match?query=seeded_test_user"

{"userId":"082347fe0f7da026fa6187fc00b05c55","username":"seeded_test_user@digital.justice.gov.uk","isActive":true,
"lastLoginAt":{"date":"2020-12-03 11:01:34.000000","timezone_type":1,"timezone":"+00:00"},
"updatedAt":{"date":"2020-01-21 15:15:53.000000","timezone_type":1,"timezone":"+00:00"},
"createdAt":{"date":"2020-01-21 15:15:11.007119","timezone_type":1,"timezone":"+00:00"},
"activatedAt":{"date":"2020-01-21 15:15:53.000000",
"timezone_type":1,"timezone":"+00:00"},"lastFailedLoginAttemptAt":null,"failedLoginAttempts":0}
```
