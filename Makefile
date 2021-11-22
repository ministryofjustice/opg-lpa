SHELL := '/bin/bash'

# Must be set to some string.
# Used to send notifications to end users from service-front.
SENDGRID ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_email_sendgrid_api_key | jq -r .'SecretString')

# Used by service-front for making payments.
# Can be disabled in dev, just don't offer to pay when completing LPA.
GOVPAY ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_gov_pay_key | jq -r .'SecretString')

# Used by service-front for postcode lookup.
ORDNANCESURVEY ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_os_places_hub_license_key | jq -r .'SecretString')

# Used for emails sent by service-api's account cleanup CLI script.
NOTIFY ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_api_notify_api_key | jq -r .'SecretString')

# Used in service-admin to determine which logged-in user has admin rights.
# This user is in the test data seeded into the system.
ADMIN_USERS := "seeded_test_user@digital.justice.gov.uk"

.PHONY: all
all:
	@${MAKE} dc-up

.PHONY: reset
reset:
	@${MAKE} dc-build-clean
	@${MAKE} dc-run

.PHONY: dc-run
dc-run:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run front-composer | xargs -L1 echo front-composer: &

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	sleep 20; docker-compose run admin-composer | xargs -L1 echo admin-composer: &

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	sleep 20; docker-compose run api-composer | xargs -L1 echo api-composer: &

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	sleep 20; docker-compose run pdf-composer | xargs -L1 echo pdf-composer:

# This will make a docker network called "malpadev", used to communicate from
# the perfplatworkerproxy lambda (running in localstack) to the perfplatworker
# lambda (running as a docker container).
# The name of the network created here must match the one in the docker-compose.yml.
.PHONY: dc-up
dc-up:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	if [ "`docker network ls | grep malpadev`" = "" ] ; then docker network create malpadev ; fi; \
	docker-compose up

# target for users outside MoJ to run the stack without 3rd party integrations
.PHONY: dc-up-out
dc-up-out:
	@${MAKE} dc-up SENDGRID=- GOVPAY=- NOTIFY=- ORDNANCESURVEY=-

.PHONY: dc-build
dc-build:
	@COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose build

# remove docker containers, volumes, images left by existing system, rebuild everything
# with no-cache
# this leaves things in a state where make dc-run is needed again before starting back up
.PHONY: dc-build-clean
dc-build-clean:
	@${MAKE} dc-down
	@docker system prune -f --volumes; \
	docker rmi lpa-pdf-app || true; \
	docker rmi lpa-admin-web || true; \
	docker rmi lpa-admin-app || true; \
	docker rmi lpa-api-web || true; \
	docker rmi lpa-api-app || true; \
	docker rmi lpa-front-web || true; \
	docker rmi lpa-front-app || true; \
	docker rmi seeding || true; \
	docker rmi mocksirius || true; \
	docker rmi opg-lpa_local-config; \
	rm -fr ./service-front/node_modules/parse-json/vendor; \
	rm -fr ./service-front/node_modules/govuk_frontend_toolkit/javascripts/vendor; \
	rm -fr ./service-front/public/assets/v2/js/vendor; \
	rm -fr ./service-front/vendor; \
	rm -fr ./service-pdf/vendor; \
	if [ "`docker network ls | grep malpadev`" = "" ] ; then docker network create malpadev ; fi; \
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose build --no-cache

# only reset the front container - uesful for quick reset when only been working on front component
.PHONY: reset-front
reset-front:
	@${MAKE} dc-down
	@docker system prune -f --volumes; \
	docker rmi lpa-front-web || true; \
	docker rmi lpa-front-app || true; \
	rm -fr ./service-front/node_modules/parse-json/vendor; \
	rm -fr ./service-front/node_modules/govuk_frontend_toolkit/javascripts/vendor; \
	rm -fr ./service-front/public/assets/v2/js/vendor; \
	rm -fr ./service-front/vendor; \
	if [ "`docker network ls | grep malpadev`" = "" ] ; then docker network create malpadev ; fi; \
	docker-compose build --no-cache front-web
	docker-compose build --no-cache front-app

# only reset the front web container - uesful for quick reset after nginx.conf tweak
.PHONY: reset-front-web
reset-front-web:
	@${MAKE} dc-down
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker rmi lpa-front-web || true; \
	if [ "`docker network ls | grep malpadev`" = "" ] ; then docker network create malpadev ; fi; \
	docker-compose build --no-cache front-web

.PHONY: reset-flask
reset-flask:
	@${MAKE} dc-down
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker rmi lpa-flask-app || true; \
	docker-compose build --no-cache flask-app

# only reset the api container
.PHONY: reset-api
reset-api:
	@${MAKE} dc-down
	@docker system prune -f --volumes; \
	docker rmi lpa-api-web || true; \
	docker rmi lpa-api-app || true; \
	rm -fr ./service-api/vendor; \
	if [ "`docker network ls | grep malpadev`" = "" ] ; then docker network create malpadev ; fi; \
	docker-compose build --no-cache api-web
	docker-compose build --no-cache api-app

.PHONY: dc-down
dc-down:
	@docker-compose down --remove-orphans

.PHONY: dc-front-unit-tests
dc-front-unit-tests:
	@docker-compose run front-app /app/vendor/bin/phpunit

.PHONY: dc-unit-tests
dc-unit-tests:
	@${MAKE} dc-front-unit-tests

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run admin-app /app/vendor/bin/phpunit

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run api-app /app/vendor/bin/phpunit

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run pdf-app /app/vendor/bin/phpunit

.PHONY: functional-local
functional-local:
	docker build -f ./tests/Dockerfile  -t casperjs:latest .; \
	aws-vault exec moj-lpa-dev -- docker run -it -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=localhost:7002" --network="host" --rm casperjs:latest ./start.sh 'tests/'

.PHONY: integration-api-local
integration-api-local:
	docker build -f ./service-api/docker/app/Dockerfile -t integration-api-tests .;\
	docker run -it --network="host" --rm integration-api-tests  sh -c "cd /app/tests/integration && php ../../vendor/bin/phpunit -v"

.PHONY: cypress-local
cypress-local:
	docker rm -f cypress_tests || true
	docker build -f ./cypress/Dockerfile  -t cypress:latest .; \
	aws-vault exec moj-lpa-dev -- docker run -it -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "CYPRESS_baseUrl=https://localhost:7002" -e "CYPRESS_headless=true" --entrypoint ./cypress/start.sh -v `pwd`/cypress:/app/cypress --network="host" --name cypress_tests cypress:latest

.PHONY: cypress-local-shell
cypress-local-shell:
	aws-vault exec moj-lpa-dev -- docker run -it -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "CYPRESS_baseUrl=https://localhost:7002" -e "CYPRESS_headless=true" --entrypoint bash --network="host" -v `pwd`/cypress:/app/cypress --name cypress_tests cypress:latest

.PHONY: cypress-gui-local
UNAME_S := $(shell uname -s)

ifeq ($(UNAME_S),Darwin)
MYIP := $(shell ipconfig getifaddr en0)
cypress-gui-local:
	docker build -f ./cypress/Dockerfile  -t cypress:latest .; \
	aws-vault exec moj-lpa-dev -- docker run -it -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "DISPLAY=${MYIP}:0" -e "CYPRESS_VIDEO=true" -e "CYPRESS_baseUrl=https://localhost:7002"  -v ${PWD}/cypress:/app/cypress --entrypoint "./cypress/start.sh" --network="host" --rm cypress:latest open --project /app
endif

ifeq ($(UNAME_S),Linux)
cypress-gui-local:
	xhost + 127.0.0.1
	aws-vault exec moj-lpa-dev -- docker run -it -v ~/.Xauthority:/root/.Xauthority:ro -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e DISPLAY -e "CYPRESS_VIDEO=true" -e "CYPRESS_baseUrl=https://localhost:7002"  --entrypoint "./cypress/start.sh" --network="host" --rm cypress:latest open --project /app
endif

.PHONY: restitch
restitch:
	cypress/stitch.sh

# Start S3 Monitor and call "cypress open";
# this requires a globally-installed cypress
.PHONY: cypress-open
cypress-open:
	aws-vault exec moj-lpa-dev -- python3 cypress/S3Monitor.py &
	CYPRESS_userNumber=`node cypress/userNumber.js` ./node_modules/.bin/cypress open --project ./
