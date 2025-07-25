SHELL := '/bin/bash'

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

COMPOSER_VERSION := "2.5.5"

# Unique identifier for this version of the application
APP_VERSION := $(shell echo -n `git rev-parse --short HEAD`)

.PHONY: all
all:
	@${MAKE} dc-up

.PHONY: reset
reset:
	@${MAKE} dc-build-clean
	@${MAKE} run-composers

.PHONY: run-front-composer
run-front-composer:
	@docker run --rm -v `pwd`/service-front/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs

.PHONY: run-pdf-composer
run-pdf-composer:
	@docker run --rm -v `pwd`/service-pdf/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs

.PHONY: run-api-composer
run-api-composer:
	@docker run --rm -v `pwd`/service-api/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs

.PHONY: run-admin-composer
run-admin-composer:
	@docker run --rm -v `pwd`/service-admin/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs

.PHONY: run-shared-composer
run-shared-composer:
	@docker run --rm -v `pwd`/shared/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs

.PHONY: run-composers
run-composers:
	@docker pull composer:${COMPOSER_VERSION}; \
	${MAKE} -j run-front-composer run-pdf-composer run-api-composer run-admin-composer run-shared-composer

.PHONY: dc-up
dc-up: run-composers
	@export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	export OPG_LPA_COMMON_APP_VERSION=${APP_VERSION}; \
	aws-vault exec moj-lpa-dev -- aws ecr get-login-password --region eu-west-1 | docker login \
		--username AWS --password-stdin 311462405659.dkr.ecr.eu-west-1.amazonaws.com; \
	docker compose up -d

.PHONY: dc-build
dc-build:
	@COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker compose build

# remove docker containers, volumes, images left by existing system, rebuild everything
# with no-cache
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
	docker rmi gateway || true; \
	docker rmi mocksirius || true; \
	docker rmi opg-lpa_local-config; \
	rm -fr ./service-front/node_modules/parse-json/vendor; \
	rm -fr ./service-front/node_modules/govuk_frontend_toolkit/javascripts/vendor; \
	rm -fr ./service-front/public/assets/v2/js/vendor; \
	rm -fr ./service-front/vendor; \
	rm -fr ./service-pdf/vendor; \
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker compose build --no-cache

# standard reset only the front app container - useful for quick reset when only been working on front component
# compared to soft reset, this currently cleans up volumes too. this may turn out not to be needed , we
# may be able to go to always soft reset
.PHONY: reset-front
reset-front:
	@${MAKE} dc-down
	@docker system prune -f --volumes; \
	docker rmi lpa-front-app || true; \
	docker compose build --no-cache front-app

# hard reset only the front app container - cleaning up vendor folders too, useful when changing versions of deps
.PHONY: hard-reset-front
hard-reset-front:
	@${MAKE} dc-down
	@docker system prune -f --volumes; \
	docker rmi lpa-front-app || true; \
	rm -fr ./service-front/node_modules/parse-json/vendor; \
	rm -fr ./service-front/node_modules/govuk_frontend_toolkit/javascripts/vendor; \
	rm -fr ./service-front/public/assets/v2/js/vendor; \
	rm -fr ./service-front/vendor; \
	docker compose build --no-cache front-app

.PHONY: soft-reset-front
# soft reset only the front app container without no-cache option.
# quickest rebuild but runs risk of some staleness if not every change is picked up
soft-reset-front:
	@${MAKE} dc-down
	docker compose build front-app

# only reset the front web container - uesful for quick reset after nginx.conf tweak
.PHONY: reset-front-web
reset-front-web:
	@${MAKE} dc-down
	@export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker rmi lpa-front-web || true; \
	docker compose build --no-cache front-web

# hard reset only the api app container
.PHONY: reset-api
reset-api:
	@${MAKE} dc-down
	docker rmi lpa-api-app || true; \
	rm -fr ./service-api/vendor; \
	docker compose build --no-cache api-app

.PHONY: dc-down
dc-down:
	@docker compose down --remove-orphans

.PHONY: dc-front-unit-tests
dc-front-unit-tests:
	@docker compose run front-app /app/vendor/bin/phpunit

.PHONY: dc-unit-tests
dc-unit-tests: dc-front-unit-tests
	@docker compose run admin-app /app/vendor/bin/phpunit
	@docker compose run api-app /app/vendor/bin/phpunit
	@docker compose run pdf-app /app/vendor/bin/phpunit

.PHONY: npm-install
npm-install:
	npm install

# CYPRESS_RUNNER_* environment variables are used to consolidate setting environment
# variables detected by cypress (like CYPRESS_baseUrl) and variables which are
# only present in the cypress "environment" (i.e. passed to cypress using the -e flag).
# The runner knows which variables should be set using which mechanism. By passing
# all variables as CYPRESS_RUNNER_* env vars, picked up by the cypress_runner.py script,
# we can apply any logic about how to set vars for cypress, as well as provide
# reasonable defaults (e.g. for CYPRESS_baseUrl), in one location.
.PHONY: cypress-local
cypress-local: npm-install
	docker rm -f cypress_tests || true
	docker build -f ./cypress/Dockerfile  -t cypress:local .; \
	aws-vault exec moj-lpa-dev -- docker run -it -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY \
		-e AWS_SESSION_TOKEN -e CYPRESS_RUNNER_BASE_URL="https://localhost:7002" \
		-e CYPRESS_RUNNER_ADMIN_URL="https://localhost:7003" \
		-e CYPRESS_RUNNER_TAGS="@Signup,@StitchedPF or @StitchedHW" \
		-v `pwd`/cypress:/app/cypress --network="host" --name cypress_tests \
		--entrypoint ./cypress/cypress_start.sh cypress:local

.PHONY: s3-monitor
s3-monitor:
	aws-vault exec moj-lpa-dev -- python3 cypress/s3_monitor.py -v

.PHONY: cypress-gui
cypress-gui: npm-install
	CYPRESS_userNumber=`python3 cypress/user_number.py` CYPRESS_baseUrl="https://localhost:7002" \
		CYPRESS_adminUrl="https://localhost:7003" ./node_modules/.bin/cypress open \
		--project ./ -e stepDefinitions="cypress/e2e/common/*.js"

# Start S3 Monitor and start cypress GUI
.PHONY: cypress-open
cypress-open:
	${MAKE} -j s3-monitor cypress-gui
