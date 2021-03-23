# LPA Online Service

The Office of the Public Guardian Lasting Power of Attorney online service: Managed by opg-org-infra &amp; Terraform.

## Pre-requisites for Local Development

Set up software on your machine required to run the application locally:

* Install `make` using the native package manager (assuming you are on Mac or Linux)
* Install [docker](https://docs.docker.com/get-docker/)
* Install [docker-compose](https://docs.docker.com/compose/install/)
* Install `awscli`: while this can be done via brew, this failed for me on Linux, so I used [these instructions](https://docs.aws.amazon.com/cli/latest/userguide/install-cliv2.html) instead.
* Install [homebrew](https://docs.brew.sh/)
* Install dependencies for the Makefile using brew: `brew install aws-vault jq`

### Access to Amazon secrets

[Set up access to Amazon with MFA]().

Add a default profile which references your account to `~/.aws/config`:

```
[default]
region = eu-west-1
mfa_serial=arn:aws:iam::111111111111:mfa/your.name
```

The value for `mfa_serial` is visible in the AWS console under *My security credentials* after you've configured MFA.

Add a `moj-lpa-dev` profile to `~/.aws/config` which references the default profile; this should include the ARN of the dev operator role from AWS (you'll need webops to supply this):

```
[profile moj-lpa-dev]
role_arn=arn:aws:iam::111111111111:role/operator
source_profile = default
```

For the next step, you will need a temporary access key. Generate this using the AWS console, under *My security credentials*.

Add your default profile to aws-vault:

```
aws-vault add default
```

When prompted, enter the temporary access key you just generated via the AWS console. (You may also be prompted to create a new keyring on your machine using whatever method is natively available.)

Once this is done, check that you have access by running this command:

```
aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_email_sendgrid_api_key
```

You will be prompted for an MFA token, which should be displayed on whichever device you used to set up MFA for your Amazon account.

NB This command is run when starting the application locally, which is why you need to get this set up.

## Running the Application Locally

Download the repo via:

```
git clone git@github.com:ministryofjustice/opg-lpa.git
cd opg-lpa
```

Within `opg-lpa` directory to *run* the project for the first time use the following:

```
make dc-run
make
```

The `Makefile` will fetch secrets using `aws secretsmanager` and `docker-compose` commands together to pass along environment variables removing the need for local configuration files.

The LPA Tool service will be available via <https://localhost:7002/home>
The Admin service will be available via <https://localhost:7003>

The API service will be available (direct) via <http://localhost:7001>

After the first time, you can *run* the project by:

```
make
```

### Tests

To run the unit tests

```bash
make dc-unit-tests
```

For how to run the functional tests, please see seperate README in tests/functional directory

### Cypress functional tests

The cypress functional tests can be run with:

```bash
make cypress-local
```

Once you've run this command, it can be useful to start the cypress
container and run the tests from a shell inside it. That way, you don't need
to re-build the whole container for each test run. You can also mount your
local test directory as a volume in the container so that you can quickly
modify and re-run tests. To get a command-line in the cypress container, do:

```bash
make cypress-local-shell
```

This will give you a command prompt inside the container, from where you can
run the tests:

```bash
./cypress/start.sh
```

You can then modify the tests in your usual editor and re-run the modified tests
with the same command without having to rebuild/restart the container.

### Run the Cypress GUI via npm

the recommended way to run tests for the GUI runner is using npm. see: <https://docs.cypress.io/guides/getting-started/installing-cypress/#System-requirements>. this is useful if you want to see what the tests are physically doing.

Install cypress globally:

`npm i -g cypress`

The package.json in the root of the repo has all of the required dev dependancies for Cypress. Add plugins to this as needed using `npm i <package-name> --saveDev`

Assuming that the dev stack has been already started using `make dc-up`, open a new terminal and run:

```bash
make cypress-gui-npm
```

You should see a window appear with the features in it. This has been tested on Mac only so "Your Mileage May Vary" :tm:.

### Run Cypress GUI via XQuartz

To run the cypress tests in a GUI on Mac using XQuartz (X Windows), you need to install and start xquartz and socat :

```bash
brew install xquartz socat
socat TCP-LISTEN:6000,reuseaddr,fork UNIX-CLIENT:\"$DISPLAY\"
open -a Xquartz
```

If all is working, you will see XQuartz (X Window manager) running, and be able to start up a terminal.

Then run cypress :

```bash
make cypress-gui-local
```

You will see the Cypress GUI, which starts a browser (Chrome and Firefox are supported) to run tests.
You can edit tests and Cypress will (usually, although its not 100% perfect at spotting updates) re-run the
tests automatically as result.

### Updating composer dependencies

Composer install is run when the app containers are built, and on a standard `docker-compose up`.

It can also be run independently with:

```bash
docker-compose run <service>-composer
```

New packages can be added with:

```bash
docker-compose run <service>-composer composer require author/package
```

Packages can be removed with:

```bash
docker-compose run <service>-composer composer remove author/package
```
