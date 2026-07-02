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
	docker compose build --build-arg ENABLE_XDEBUG=0 front-app admin-app api-app pdf-app; \
	docker compose up -d --remove-orphans
	$(info ${YELLOW}starting asset watcher for service-front...${RESET})
	docker compose run --rm npm-front install
	docker compose run --rm npm-front run watch

.PHONY: dc-up-debug
dc-up-debug: run-composers
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	export OPG_LPA_COMMON_APP_VERSION=${APP_VERSION}; \
	docker compose build front-app admin-app api-app pdf-app; \
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

# To account for DNS changes, we need to restart the web containers so that nginx picks up the new IP addresses. This is particularly relevant when using docker-desktop on MacOS, where the IP address of the host can change.
.PHONY: dc-restart-web
dc-restart-web:
	@echo "Restarting web containers to refresh nginx DNS..."
	@docker compose restart front-web api-web admin-web
	@echo "Waiting for api-web (http://localhost:7001)..."
	@for i in $$(seq 1 30); do \
		if curl -s -o /dev/null --max-time 2 http://localhost:7001/; then \
			echo "  api-web OK"; break; \
		fi; \
		if [ $$i -eq 30 ]; then echo "  api-web did not become available"; exit 1; fi; \
		sleep 1; \
	done
	@echo "Waiting for front-web (https://localhost:7002)..."
	@for i in $$(seq 1 30); do \
		if curl -sk -o /dev/null --max-time 2 https://localhost:7002/; then \
			echo "  front-web OK"; break; \
		fi; \
		if [ $$i -eq 30 ]; then echo "  front-web did not become available"; exit 1; fi; \
		sleep 1; \
	done
	@echo "Waiting for admin-web (https://localhost:7003)..."
	@for i in $$(seq 1 30); do \
		if curl -sk -o /dev/null --max-time 2 https://localhost:7003/; then \
			echo "  admin-web OK"; break; \
		fi; \
		if [ $$i -eq 30 ]; then echo "  admin-web did not become available"; exit 1; fi; \
		sleep 1; \
	done
	@echo "All web containers are available."

.PHONY: dc-down
dc-down:
	@docker compose down --remove-orphans

.PHONY: dc-front-unit-tests
dc-front-unit-tests:
	@docker compose run --rm --no-deps -v `pwd`/service-front/build/coverage:/app/build/coverage front-app /app/vendor/bin/phpunit

.PHONY: dc-admin-unit-tests
dc-admin-unit-tests:
	@docker compose run --rm --no-deps -v `pwd`/service-admin/build/coverage:/app/build/coverage admin-app /app/vendor/bin/phpunit

.PHONY: dc-api-unit-tests
dc-api-unit-tests:
	@docker compose run --rm --no-deps -v `pwd`/service-api/build/coverage:/app/build/coverage api-app /app/vendor/bin/phpunit

.PHONY: dc-pdf-unit-tests
dc-pdf-unit-tests:
	@docker compose run --rm --no-deps -v `pwd`/service-pdf/build/coverage:/app/build/coverage pdf-app /app/vendor/bin/phpunit

.PHONY: dc-shared-unit-tests
dc-shared-unit-tests:
	@docker compose run --rm --no-deps -v `pwd`/shared/build/coverage:/shared/build/coverage pdf-app /app/vendor/bin/phpunit /shared/module/MakeShared/tests

.PHONY: dc-unit-tests
dc-unit-tests: dc-front-unit-tests dc-admin-unit-tests dc-api-unit-tests dc-pdf-unit-tests dc-shared-unit-tests

# Reset ownership of node_modules if it was previously written by Docker (which runs as root),
# which would cause npm ci to fail with EACCES permission errors. Only runs if the owner is wrong
# to avoid an unnecessary sudo prompt.
.PHONY: npm-install
npm-install:
	@if [ -d node_modules ] && [ "$$(stat -f '%u' node_modules)" != "$$(id -u)" ]; then sudo chown -R $$(id -u):$$(id -g) node_modules; fi
	npm ci --ignore-scripts

# Creates a local virtualenv with the python-api-client dependencies so cy.exec() calls work when
# running cypress open locally. The Docker-based cypress image installs these via apt instead.
.PHONY: python-api-venv
python-api-venv:
	@UV_PROJECT_ENVIRONMENT=$(CURDIR)/venv uv sync --locked --directory tests/python-api-client

.PHONY: cypress-open
cypress-open: npm-install python-api-venv
	CYPRESS_userNumber=`python3 cypress/user_number.py` CYPRESS_baseUrl="https://localhost:7004" \
		CYPRESS_adminUrl="https://localhost:7003" ./node_modules/.bin/cypress open \
		--project ./ -e stepDefinitions="cypress/e2e/common/*.js"

# Provide name of the spec file (assuming it is in cypress/e2e/) e.g. cypress-run-spec SPEC=Admin.feature
# Note that the first -e is an argument to docker compose run and the second an argument to cypress run, so these need to be positioned exactly as they are
cypress-run-spec:
	docker compose run --rm -v ./cypress/screenshots:/app/cypress/screenshots -e CYPRESS_userNumber=`python3 cypress/user_number.py` -e CYPRESS_screenshotOnRunFailure=true cypress --spec cypress/e2e/${SPEC} -e stepDefinitions="/app/cypress/e2e/common/*.js"

# This should be used in the form : make cypress-run-tags TAGS=@Signup. This is mainly used by CI, its normally more convenient locally to use cypress-run-spec
# Note that the first -e is an argument to docker compose run and the second an argument to cypress run, so these need to be positioned exactly as they are
cypress-run-tags:
	docker compose run --rm -v ./cypress/screenshots:/app/cypress/screenshots -e CYPRESS_userNumber=`python3 cypress/user_number.py` -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",TAGS="${TAGS}"

# Creates and runs stitched test suites for visual regression testing.
cypress-run-stitched-suites:
	@pushd cypress && python3 stitch.py && popd
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	CYPRESS_userNumber=`python3 cypress/user_number.py` && \
	docker compose run --rm -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/regressions:/app/cypress/regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false --expose visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",TAGS="@Signup" && \
	docker compose run --rm -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/regressions:/app/cypress/regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false --expose visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",TAGS="@StitchedHW or @StitchedPF or @StitchedClone"


.PHONY: cypress-update-baselines-hw cypress-update-baselines-pf cypress-update-baselines-clone
cypress-update-baselines-hw: _cypress-stitch
	@${MAKE} _cypress-run-baseline-suite SUITE_TAG=@StitchedHW

cypress-update-baselines-pf: _cypress-stitch
	@${MAKE} _cypress-run-baseline-suite SUITE_TAG=@StitchedPF

cypress-update-baselines-clone: _cypress-stitch
	@${MAKE} _cypress-run-baseline-suite SUITE_TAG=@StitchedClone

.PHONY: _cypress-stitch
_cypress-stitch:
	@pushd cypress && python3 stitch.py && popd

# Internal helper - runs the baseline cypress commands without stitching. Expects SUITE_TAG to be set.
.PHONY: _cypress-run-baseline-suite
_cypress-run-baseline-suite:
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	CYPRESS_userNumber=`python3 cypress/user_number.py` && \
	docker compose run --rm -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/regressions:/app/cypress/regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false --expose updateBaseline=true,visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",TAGS="@Signup" && \
	docker compose run --rm -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/regressions:/app/cypress/regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false --expose updateBaseline=true,visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",TAGS="${SUITE_TAG}"

# Replicates CI cypress runs locally to ensure visual regression test baseline images use the same user to keep
# consistent page dimensions and LPA data for each stitched suite.
cypress-update-all-baselines: _cypress-stitch
	@${MAKE} _cypress-run-baseline-suite SUITE_TAG=@StitchedHW
	@${MAKE} _cypress-run-baseline-suite SUITE_TAG=@StitchedPF
	@${MAKE} _cypress-run-baseline-suite SUITE_TAG=@StitchedClone

dc-phpcs-fix:
	docker compose build phpcs
	docker compose run --rm --no-deps -q phpcs

dc-phpcs-check:
	docker compose build phpcs
	docker compose run --rm --no-deps --entrypoint "./vendor/bin/phpcs --standard=/app/config/phpcs.xml.dist" phpcs --basepath=/app --report=full --report-checkstyle=/app/output/phpcs-report.xml

dc-clear-cache:
	docker compose exec admin-app rm -f /app/tmp/config-cache-opg-lpa-admin.php
	docker compose exec front-app rm -f /app/tmp/config-cache-opg-lpa-front.php
	docker compose exec front-app rm -rf /tmp/twig_cache
	docker compose exec api-app rm -f /app/tmp/config-cache-opg-lpa-api.php
	rm -fr service-front/twig-cache/*

update-secrets-baseline:
	detect-secrets scan --baseline .secrets.baseline

.PHONY: psql
psql:
	@docker exec -it lpa-postgres psql --username=lpauser --dbname=lpadb

# --- MEZZIO TARGETS ---

MEZZIO_COMPOSE := docker compose -f docker-compose.mezzio.yml

.PHONY: mezzio-run-composer
mezzio-run-composer:
	@docker run --rm -v `pwd`/service-front/mezzio/:/app/ composer:${COMPOSER_VERSION} composer install --prefer-dist --no-interaction --no-scripts

.PHONY: mezzio-build
mezzio-build: mezzio-run-composer run-api-composer run-admin-composer run-pdf-composer
	@COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 ${MEZZIO_COMPOSE} build --build-arg ENABLE_XDEBUG=0

.PHONY: mezzio-up
mezzio-up: mezzio-build
	$(info ${YELLOW}starting mezzio app on https://localhost:7004${RESET})
	@export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_COMMON_APP_VERSION=${APP_VERSION}; \
	${MEZZIO_COMPOSE} up -d --remove-orphans
	docker compose run --rm npm-front install
	docker compose run --rm npm-front run watch

.PHONY: mezzio-down
mezzio-down:
	@${MEZZIO_COMPOSE} down --remove-orphans

.PHONY: mezzio-logs
mezzio-logs:
	@${MEZZIO_COMPOSE} logs -f

.PHONY: mezzio-dev-enable
mezzio-dev-enable:
	@docker run --rm -v `pwd`/service-front/mezzio/:/app/ composer:${COMPOSER_VERSION} composer development-enable

.PHONY: mezzio-dev-disable
mezzio-dev-disable:
	@docker run --rm -v `pwd`/service-front/mezzio/:/app/ composer:${COMPOSER_VERSION} composer development-disable

.PHONY: mezzio-reset
mezzio-reset:
	@${MAKE} mezzio-down
	@docker rmi lpa-mezzio-app lpa-mezzio-web lpa-mezzio-ssl lpa-pdf-app lpa-admin-app lpa-admin-web lpa-admin-ssl 2>/dev/null || true
	@rm -fr ./service-front/mezzio/vendor
	@${MAKE} mezzio-up

.PHONY: mezzio-seed
mezzio-seed:
	@${MEZZIO_COMPOSE} run --rm seeding

.PHONY: mezzio-clear-cache
mezzio-clear-cache:
	@${MEZZIO_COMPOSE} exec mezzio-app rm -f /app/data/cache/config-cache.php

.PHONY: mezzio-cypress-run-spec
mezzio-cypress-run-spec:
	${MEZZIO_COMPOSE} run --rm --no-deps -v ./cypress/screenshots:/app/cypress/screenshots -e CYPRESS_userNumber=`python3 cypress/user_number.py` -e CYPRESS_screenshotOnRunFailure=true cypress-mezzio --spec cypress/e2e/${SPEC} -e stepDefinitions="/app/cypress/e2e/common/*.js"

# This should be used in the form : make mezzio-cypress-run-tags TAGS=@Signup. This is mainly used by CI, its normally more convenient locally to use mezzio-cypress-run-spec
# Note that the first -e is an argument to docker compose run and the second an argument to cypress run, so these need to be positioned exactly as they are
.PHONY: mezzio-cypress-run-tags
mezzio-cypress-run-tags:
	${MEZZIO_COMPOSE} run --rm --no-deps -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -e CYPRESS_userNumber=`python3 cypress/user_number.py` -e CYPRESS_screenshotOnRunFailure=true cypress-mezzio --headless --config video=false -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",TAGS="${TAGS}"

# Runs the "remaining" mezzio cypress tests - everything not covered by stitched suites or signup.
# Mirrors the exclusion expression used in CI (workflow_merge_queue.yml cypress_tests_Remaining),
# plus @Admin which requires cross-origin admin-ssl navigation not supported locally.
.PHONY: mezzio-cypress-run-remaining
mezzio-cypress-run-remaining:
	@${MAKE} mezzio-cypress-run-tags TAGS="not @Signup and not @PartOfStitchedRun and not @StitchedHW and not @StitchedPF and not @StitchedClone and not @CorrespondentReuse and not @SignupIncluded and not @AdminSystemMessage and not @CheckoutPaymentGateway and not @Ping"

.PHONY: mezzio-cypress-open
mezzio-cypress-open: npm-install python-api-venv
	CYPRESS_userNumber=`python3 cypress/user_number.py` CYPRESS_baseUrl="https://localhost:7004" \
		CYPRESS_adminUrl="https://localhost:7003" ./node_modules/.bin/cypress open \
		--project ./ -e stepDefinitions="cypress/e2e/common/*.js"

.PHONY: mezzio-cypress-run-stitched-suites
mezzio-cypress-run-stitched-suites: _cypress-stitch
	$(info ${YELLOW}running mezzio stitched cypress suites${RESET})
	@export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	CYPRESS_userNumber=`python3 cypress/user_number.py` && \
	${MEZZIO_COMPOSE} run --rm --no-deps -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/mezzio-regressions:/app/cypress/mezzio-regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress-mezzio --headless --config video=false --expose mezzio=true,visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",TAGS="@Signup" && \
	${MEZZIO_COMPOSE} run --rm --no-deps -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/mezzio-regressions:/app/cypress/mezzio-regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress-mezzio --headless --config video=false --expose mezzio=true,visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",TAGS="@StitchedHW or @StitchedPF or @StitchedClone"

.PHONY: mezzio-cypress-update-baselines-hw mezzio-cypress-update-baselines-pf mezzio-cypress-update-baselines-clone
mezzio-cypress-update-baselines-hw: _cypress-stitch
	@${MAKE} _mezzio-cypress-run-baseline-suite SUITE_TAG=@StitchedHW

mezzio-cypress-update-baselines-pf: _cypress-stitch
	@${MAKE} _mezzio-cypress-run-baseline-suite SUITE_TAG=@StitchedPF

mezzio-cypress-update-baselines-clone: _cypress-stitch
	@${MAKE} _mezzio-cypress-run-baseline-suite SUITE_TAG=@StitchedClone

# Internal helper - runs the mezzio baseline cypress commands without stitching. Expects SUITE_TAG to be set.
.PHONY: _mezzio-cypress-run-baseline-suite
_mezzio-cypress-run-baseline-suite:
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	CYPRESS_userNumber=`python3 cypress/user_number.py` && \
	${MEZZIO_COMPOSE} run --rm --no-deps -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/mezzio-regressions:/app/cypress/mezzio-regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress-mezzio --headless --config video=false --expose mezzio=true,updateBaseline=true,visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",TAGS="@Signup" && \
	${MEZZIO_COMPOSE} run --rm --no-deps -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/mezzio-regressions:/app/cypress/mezzio-regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress-mezzio --headless --config video=false --expose mezzio=true,updateBaseline=true,visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",TAGS="${SUITE_TAG}"

# Update all mezzio baseline images sequentially
.PHONY: mezzio-cypress-update-all-baselines
mezzio-cypress-update-all-baselines: _cypress-stitch
	@${MAKE} _mezzio-cypress-run-baseline-suite SUITE_TAG=@StitchedHW
	@${MAKE} _mezzio-cypress-run-baseline-suite SUITE_TAG=@StitchedPF
	@${MAKE} _mezzio-cypress-run-baseline-suite SUITE_TAG=@StitchedClone

.PHONY: mezzio-restart-web
mezzio-restart-web:
	@echo "Restarting mezzio web containers to refresh nginx DNS..."
	@${MEZZIO_COMPOSE} restart mezzio-web api-web admin-web
	@echo "Waiting for api-web (http://localhost:7001)..."
	@for i in $$(seq 1 30); do \
		if curl -s -o /dev/null --max-time 2 http://localhost:7001/; then \
			echo "  api-web OK"; break; \
		fi; \
		if [ $$i -eq 30 ]; then echo "  api-web did not become available"; exit 1; fi; \
		sleep 1; \
	done
	@echo "Waiting for mezzio-ssl (https://localhost:7004)..."
	@for i in $$(seq 1 30); do \
		if curl -sk -o /dev/null --max-time 2 https://localhost:7004/; then \
			echo "  mezzio-ssl OK"; break; \
		fi; \
		if [ $$i -eq 30 ]; then echo "  mezzio-ssl did not become available"; exit 1; fi; \
		sleep 1; \
	done
	@echo "Waiting for admin-ssl (https://localhost:7003)..."
	@for i in $$(seq 1 30); do \
		if curl -sk -o /dev/null --max-time 2 https://localhost:7003/; then \
			echo "  admin-ssl OK"; break; \
		fi; \
		if [ $$i -eq 30 ]; then echo "  admin-ssl did not become available"; exit 1; fi; \
		sleep 1; \
	done
	@echo "All mezzio web containers are available."

.PHONY: mezzio-unit-tests
mezzio-unit-tests:
	@${MEZZIO_COMPOSE} exec mezzio-app php /app/vendor/bin/phpunit -c /app/phpunit.xml $(ARGS)

.PHONY: mezzio-psalm
mezzio-psalm:
	@${MEZZIO_COMPOSE} exec mezzio-app php /app/vendor/bin/psalm -c /app/psalm.xml --no-cache $(ARGS)
