SHELL := '/bin/bash'

# Used by service-front for making payments.
# Can be disabled in dev, just don't offer to pay when completing LPA.
GOVPAY ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_gov_pay_key | jq -r .'SecretString')

# Used by service-front for postcode lookup.
ORDNANCESURVEY ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_os_places_hub_license_key | jq -r .'SecretString')

# Used for emails sent by service-api's account cleanup CLI script.
NOTIFY ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id local/opg_lpa_api_notify_api_key | jq -r .'SecretString')

# Used in service-admin to determine which logged-in user has admin rights.
# This user is in the test data seeded into the system.
ADMIN_USERS := "seeded_test_user@digital.justice.gov.uk"

COMPOSER_VERSION := "2.8.11"

# Unique identifier for this version of the application
APP_VERSION := $(shell echo -n `git rev-parse --short HEAD`)

PHPCS_REPORT ?= full

#COLORS
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

.PHONY: all
all:
	@${MAKE} dc-up

.PHONY: reset
reset:
	@${MAKE} dc-build-clean
	@${MAKE} run-composers

.PHONY: run-front-composer
run-front-composer:
	@docker run --rm -v `pwd`/service-front/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts

.PHONY: run-pdf-composer
run-pdf-composer:
	@docker run --rm -v `pwd`/service-pdf/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts

.PHONY: run-api-composer
run-api-composer:
	@docker run --rm -v `pwd`/service-api/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts

.PHONY: run-admin-composer
run-admin-composer:
	@docker run --rm -v `pwd`/service-admin/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts

.PHONY: run-shared-composer
run-shared-composer:
	@docker run --rm -v `pwd`/shared/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts

.PHONY: run-composers
run-composers:
	@docker pull composer:${COMPOSER_VERSION}; \
	${MAKE} -j run-front-composer run-pdf-composer run-api-composer run-admin-composer run-shared-composer

# use make front-composer-update PACKAGE=symfony\/validator\:v5.4.43
# you'll need to escape the \ and : as above
.PHONY: front-composer-update
front-composer-update:
	@docker run --rm -v `pwd`/service-front/:/app/ composer:${COMPOSER_VERSION} composer update $(PACKAGE) --prefer-dist --no-interaction --no-scripts

# remove a package, same format for PACKAGE= as above
.PHONY: front-composer-remove
front-composer-remove:
	@docker run --rm -v `pwd`/service-front/:/app/ composer:${COMPOSER_VERSION} composer remove $(PACKAGE)  --no-install

#run composer outdated in front container
.PHONY: front-composer-outdated
front-composer-outdated:
	@docker run --rm -v `pwd`/service-front/:/app/ composer:${COMPOSER_VERSION} composer outdated

# use make api-composer-update PACKAGE=symfony\/validator\:v5.4.43
# you'll need to escape the \ and : as above
.PHONY: api-composer-update
api-composer-update:
	@docker run --rm -v `pwd`/service-api/:/app/ composer:${COMPOSER_VERSION} composer update $(PACKAGE) --prefer-dist --no-interaction --no-scripts

# remove a package, same format for PACKAGE= as above
.PHONY: api-composer-remove
api-composer-remove:
	@docker run --rm -v `pwd`/service-api/:/app/ composer:${COMPOSER_VERSION} composer remove $(PACKAGE)  --no-install

#run composer outdated in front container
.PHONY: api-composer-outdated
api-composer-outdated:
	@docker run --rm -v `pwd`/service-api/:/app/ composer:${COMPOSER_VERSION} composer outdated

# use make api-composer-why PACKAGE=symfony\/validator\:v5.4.43
# you'll need to escape the \ and : as above
.PHONY: api-composer-why
api-composer-why:
	@docker run --rm -v `pwd`/service-api/:/app/ composer:${COMPOSER_VERSION} composer why $(PACKAGE)

.PHONY: dc-up
dc-up: run-composers
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	export OPG_LPA_COMMON_APP_VERSION=${APP_VERSION}; \
	XDEBUG_MODE=off docker compose up -d --remove-orphans

.PHONY: dc-up-debug
dc-up-debug: run-composers
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	export OPG_LPA_COMMON_APP_VERSION=${APP_VERSION}; \
	docker compose up -d --remove-orphans

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
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password.${RESET})
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
	@docker compose run --no-deps front-app /app/vendor/bin/phpunit

.PHONY: dc-admin-unit-tests
dc-admin-unit-tests:
	@docker compose run --no-deps admin-app /app/vendor/bin/phpunit

.PHONY: dc-api-unit-tests
dc-api-unit-tests:
	@docker compose run --no-deps api-app /app/vendor/bin/phpunit

.PHONY: dc-pdf-unit-tests
dc-pdf-unit-tests:
	@docker compose run --no-deps pdf-app /app/vendor/bin/phpunit

.PHONY: dc-shared-unit-tests
dc-shared-unit-tests:
	@docker compose run --no-deps pdf-app /app/vendor/bin/phpunit /shared/module/MakeShared/tests

.PHONY: dc-unit-tests
dc-unit-tests: dc-front-unit-tests dc-admin-unit-tests dc-api-unit-tests dc-pdf-unit-tests dc-shared-unit-tests

.PHONY: npm-install
npm-install:
	npm ci --ignore-scripts

.PHONY: cypress-open
cypress-open: npm-install
	CYPRESS_userNumber=`python3 cypress/user_number.py` CYPRESS_baseUrl="https://localhost:7002" \
		CYPRESS_adminUrl="https://localhost:7003" ./node_modules/.bin/cypress open \
		--project ./ -e stepDefinitions="cypress/e2e/common/*.js"

.PHONY: cypress-open-mezzio-test
cypress-open-mezzio-test: npm-install
	CYPRESS_baseUrl="https://localhost:7005" \
    ./node_modules/.bin/cypress open \
		--project ./ -e stepDefinitions="cypress/e2e/common/*.js"

# Provide the spec name (assuming it is in cypress/e2e) e.g. cypress-run-spec SPEC=Admin.feature
# Note that the first -e is an argument to docker compose run and the second an argument to cypress run, so these need to be positioned exactly as they are
cypress-run-spec:
	docker compose run --rm -e CYPRESS_userNumber=`python3 cypress/user_number.py` cypress --spec cypress/e2e/${SPEC} -e stepDefinitions="/app/cypress/e2e/common/*.js"

# Provide full path for spec name (assuming it is in cypress/e2e) e.g. cypress-run-spec SPEC=FrontMezzioTest.feature
cypress-run-spec-mezzio-test:
	docker compose -f docker-compose.front-mezzio-test.yml run --rm cypress-mezzio-test --spec cypress/e2e/${SPEC} -e stepDefinitions="/app/cypress/e2e/common/*.js"

# This should be used in the form : make cypress-run-tags TAGS="@Signup". This is mainly used by CI, its normally more convenient locally to use cypress-run-spec
# Note that the first -e is an argument to docker compose run and the second an argument to cypress run, so these need to be positioned exactly as they are
cypress-run-tags:
	docker compose run --rm -e CYPRESS_userNumber=`python3 cypress/user_number.py` cypress --headless --config video=false -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",TAGS="${TAGS}"

# Provide full path for spec name e.g. cypress-run-spec-update-baseline SPEC=cypress/e2e/Admin.feature
# Note that the first -e is an argument to docker compose run and the second an argument to cypress run, so these need to be positioned exactly as they are
cypress-run-spec-update-baseline:
	docker compose run --rm -e CYPRESS_updateBaseline="1" cypress --spec cypress/e2e/${SPEC} -e stepDefinitions="/app/cypress/e2e/common/*.js"

dc-phpcs-fix:
	docker compose build phpcs
	docker compose run --rm --no-deps -q phpcs

dc-phpcs-check:
	docker compose build phpcs
	docker compose run --rm --no-deps --entrypoint "./vendor/bin/phpcs --standard=/app/config/phpcs.xml.dist" phpcs --report=${PHPCS_REPORT}

dc-clear-cache:
	docker compose exec admin-app rm -f /app/tmp/config-cache-opg-lpa-admin.php
	docker compose exec front-app rm -f /app/tmp/config-cache-opg-lpa-front.php
	docker compose exec front-app rm -rf /tmp/twig_cache
	docker compose exec api-app rm -f /app/tmp/config-cache-opg-lpa-api.php
	rm -fr service-front/twig-cache/*

update-secrets-baseline:
	detect-secrets scan --baseline .secrets.baseline
