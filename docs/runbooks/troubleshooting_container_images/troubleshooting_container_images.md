
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

Sometimes, when circleci builds docker images,it may produce results that are different or unexpected to what you are seeing on your local machine. Reasons this may happen include:

- Issues with composer.lock, either changed or not present.
- Docker images e.g. php, python are pulled from the `:latest` tag and a breaking update happens to the image.
- Issues with `npm, grunt or gulp` for example.

You may want to debug the images generated in more detail, perhaps even go through the approach that circleci uses, but locally. This doc helps you do that.

**Note:** this guide is not exhaustive, so please add items for new scenarios.

## Prerequisites

you will need:

- aws-vault.
- profile in the `~/.aws/config/` that is a dev operator.
- AWS CLI (v1) - as this is in use on circle right now.
- Docker Desktop for Mac
- PHPStorm

please see [Setting up AWS Credentials](../setting-up-aws-credentials/setting-up-credentials.md) for details of how to set up aws cli and aws-vault on your local machine.

## Scenarios

Here's a number of things that will help when debugging a container issue:

- If an issue was spotted in PHPUnit tests in circle CI, you could [Build the dockerfile locally](#build-dockerfile-locally)
- If an issue was spotted after the container was pushed, and later spun up you can also [Pull the existing ECR image](#pull-an-existing-ecr-image)
- For a quick dignostic of the unit tests on the container: [Run PHPUnit directly on the container](#run-phpunit-directly-on-the-container)
- To run the container in PHP Storm: [Set up individual docker run configuration for PHPStorm](#set-up-individual-docker-run-configuration-for-phpstorm)
- To deep debug the tests on container: [Setting up debugging of PHPUnit tests in PHPStorm](#setting-up-debugging-of-phpunit-tests-in-phpstorm)
- To check whether the API is working correctly (in isolation from the front ends): [Testing service-api manually with cURL](#testing-service-api-manually-with-curl)

Examples below cover api container, but equally apply for front, admin and pdf containers too.

## Build dockerfile locally

Build the dockerfile for container image you wish to debug - it's best to keep names as per how the circleci produces, if you want to mirror the CI process. e.g

 ``` bash
 docker build -f service-api/docker/app/Dockerfile --progress=plain --no-cache -t 311462405659.dkr.ecr.eu-west-1.amazonaws.com/online-lpa/api_app
 ```

## Pull an existing ECR image

Login to ECR and pull the container using the following commands. You can also replace the `:latest` with a specific tag; e.g. `master-<commit-id>` where `commit-id` is the commit that generated the build, so you can pinpoint a specific image:

``` bash
aws-vault exec moj-lpa-dev --  aws ecr get-login-password --region eu-west-1 | docker login --username AWS --password-stdin 311462405659.dkr.ecr.eu-west-1.amazonaws.com
docker pull 311462405659.dkr.ecr.eu-west-1.amazonaws.com/online-lpa/api_app:latest
```

## Run PHPUnit directly on the container

run the image up, enable xdebug on the container and run the tests, using the following commands:

``` bash
docker run -d --env AWS_ACCESS_KEY_ID='devkey' --env AWS_SECRET_ACCESS_KEY='devkey' --name api-tests 311462405659.dkr.ecr.eu-west-1.amazonaws.com/online-lpa/api_app:latest
docker exec api-tests docker-php-ext-enable xdebug
docker exec api-tests /app/vendor/bin/phpunit -d memory_limit=256M

```

## Set up individual docker run configuration for PHPStorm

1 . set up PHPStorm to attach to your new image using a docker debug configuration

   1. in the configuration dropdown (top corner where the play and debug buttons are) select `Edit configurations..`
   2. hit the `+` and add a `Dockerfile` configuration
   3. set image name (as in previous step)
   4. set container name as set in previous step
   5. you may need to set AWS environment variables - these should be all you need:
      1. `AWS_ACCESS_KEY_ID='devkey'; AWS_SECRET_ACCESS_KEY='devkey'`
   6. in the command preview you will see the constructed command look like this:

``` bash
    docker run -P --env AWS_ACCESS_KEY_ID='devkey' --env AWS_SECRET_ACCESS_KEY='devkey' --name api-tests 311462405659.dkr.ecr.eu-west-1.amazonaws.com/online-lpa/api_app:latest
```

## Setting up debugging of PHPUnit tests in PHPStorm

this is currently quite involved so we may want to add this as an option to the .idea files.

This consists of 3 main steps:

- Creating the configuration for PHPUnit debugging.
- Setting up a PHP interpreter.
- Creating the remote interpreter for the test framework.

### Create the configuration

1. in the configuration dropdown (top corner where the play and debug buttons are) select `Edit configurations..`
2. hit the `+` in the `Run/Debug Configurations` dialog and add a `PHPUnit` configuration.
3. select `Test Runner` option to be `Defined in the configuration file`
4. check the `Use alternative configuration file:` and enter the path for your phpunit.xml
5. this is usually in the root of the service your debugging on your source code. e.g `<source-root>/opg-lpa/service-api`

### Configure the remote interpreter

1. in the `Command Line` section, you will need to set up the docker container to be the interpreter. This is similar to the existing docker-compose setup
2. open `...` next to the `Interpreter:` dropdown
3. in the `CLI Interpreters` dialog, hit the `+` to add `From Docker, Vagrant,VM, WSL, Remote...`
4. the dialog will pop up and select the `Docker` radio button.
5. in the `image name:` select the tag of the image you want to debug, click ok.
6. you will have to find the debugger extension path in the container.
7. connect to a running instance of the image above to work this out.

    ``` bash
    docker run -d --env AWS_ACCESS_KEY_ID='devkey' --env AWS_SECRET_ACCESS_KEY='devkey' --name api-tests 311462405659.dkr.ecr.eu-west-1.amazonaws.com/online-lpa/api_app:latest
    docker exec -it api-tests sh
    ```

8. at the `/app #` prompt enter `cd /usr/local/lib/php/extensions/` then enter `ls`.
9. you will see a folder labelled something similar to: `no-debug-non-zts-<YYYYMMDD>`, `cd` into it.
10. Take the entire folder path, appending `/xdebug.so` and paste it into the `Debugger extension:` field in the open dialog in PHP Storm, for example `/usr/local/lib/php/extensions/no-debug-non-zts-20180731/xdebug.so` (the path for PHP 7.4)
11. Before hitting Apply in PHP storm, `exit` the shell and stop the container e.g. `docker stop api-tests`
12. Hit Apply and OK.

### Configure the remote interpreter for the test framework

1. Set up the test framework by selecting the cog icon on the right of the `Use alternative configuration file:` text box.
2. Click `+` in the `Test Frameworks` dialog that appears
3. Select Configuration Type as `PHPUnit by Remote Interpreter`
4. In the `PHP by Remote Interpreter` dialog selct your previously created interpreter option, most likley named the same as you container image tag., and click `OK`
5. Click the folder icon on the Docker container>remove volume from the docker container settings using the folder icon, and replace with a new entry mapping your root of your service codebase e.g. `/users/myusername/opg-lpa/service-api` to `/app`
6. This should change the path mappings to something similar to `<Project root>/service-api -> /app`
7. Under the `PHPUnit library` section set the `Path to script` folder to `/app/vendor/autoload.php`
8. Click OK.
9. You should now be able to set breakpoints and debug the PHPUnit tests.

## Testing service-api manually with cURL

If you are having issues with the application, you can check whether the API part of it is responding correctly by manually invoking it with cURL.

When running under docker-compose, the API is on http://localhost:7001/

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
