SHELL := '/bin/bash'

# Used by service-front for making payments.
# Can be disabled in dev, just don't offer to pay when completing LPA.
GOVPAY ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_gov_pay_key | jq -r .'SecretString')

# Used by service-front for postcode lookup.
ORDNANCESURVEY ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_os_places_hub_license_key | jq -r .'SecretString')

# Used for emails sent by service-api's account cleanup CLI script.
NOTIFY ?= $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id local/opg_lpa_api_notify_api_key | jq -r .'SecretString')

COMPOSER_VERSION := 2.8.11

# Unique identifier for this version of the application
APP_VERSION := $(shell git rev-parse --short HEAD)

#COLORS
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

.PHONY: all
all:
	@${MAKE} dc-up

.PHONY: ecrlogin
ecrlogin:
	aws-vault exec management -- aws ecr get-login-password --region eu-west-1 | docker login --username AWS --password-stdin 311462405659.dkr.ecr.eu-west-1.amazonaws.com

.PHONY: reset
reset:
	@${MAKE} dc-build-clean
	@${MAKE} all-composer-install

# ----- Composer front ----- #
.PHONY: front-composer-install
front-composer-install:
	@docker compose run -T --rm composer-front install --prefer-dist --no-interaction --no-scripts --ignore-platform-reqs

# use make front-composer-update PACKAGE=symfony\/validator\:v5.4.43
.PHONY: front-composer-update
front-composer-update:
	@docker compose run --rm composer-front update $(PACKAGE) --prefer-dist --no-interaction --no-scripts

# Usage: make front-composer-require PACKAGE=vendor\/package
# For a version constraint: make front-composer-require PACKAGE=vendor\/package\:^1.0
.PHONY: front-composer-require
front-composer-require:
	@docker compose run --rm composer-front require $(PACKAGE)

# remove a package, same format for PACKAGE= as above
.PHONY: front-composer-remove
front-composer-remove:
	@docker compose run --rm composer-front remove $(PACKAGE) --no-install

#run composer outdated in front container
.PHONY: front-composer-outdated
front-composer-outdated:
	@docker compose run --rm composer-front outdated

# use make front-composer-why PACKAGE=symfony\/validator
.PHONY: front-composer-why
front-composer-why:
	@docker run --rm -v `pwd`/service-front/:/app/ composer:${COMPOSER_VERSION} composer why $(PACKAGE)

# ----- Composer api ----- #
.PHONY: api-composer-install
api-composer-install:
	@docker compose run -T --rm composer-api install --prefer-dist --no-interaction --no-scripts

# Usage: make api-composer-require PACKAGE=vendor\/package
# For a version constraint: make api-composer-require PACKAGE=vendor\/package\:^1.0
.PHONY: api-composer-require
api-composer-require:
	@docker compose run --rm composer-api require $(PACKAGE)

# use make api-composer-update PACKAGE=symfony\/validator\:v5.4.43
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

# use make api-composer-why PACKAGE=symfony\/validator
.PHONY: api-composer-why
api-composer-why:
	@docker run --rm -v `pwd`/service-api/:/app/ composer:${COMPOSER_VERSION} composer why $(PACKAGE)

.PHONY: pdf-composer-install
pdf-composer-install:
	@docker compose run -T --rm composer-pdf install --prefer-dist --no-interaction --no-scripts

# ----- Composer admin ----- #
.PHONY: admin-composer-install
admin-composer-install:
	@docker compose run -T --rm composer-admin install --prefer-dist --no-interaction --no-scripts

# use make admin-composer-update PACKAGE=symfony\/validator\:v5.4.43
.PHONY: admin-composer-update
admin-composer-update:
	@docker run --rm -v `pwd`/service-admin/:/app/ composer:${COMPOSER_VERSION} composer update $(PACKAGE) --prefer-dist --no-interaction --no-scripts

# Usage: make admin-composer-require PACKAGE=vendor\/package
# For a version constraint: make admin-composer-require PACKAGE=vendor\/package\:^1.0
.PHONY: admin-composer-require
admin-composer-require:
	@docker compose run --rm composer-admin require $(PACKAGE)

# ----- Composer pdf ----- #
# use make pdf-composer-update PACKAGE=symfony\/validator\:v5.4.43
.PHONY: pdf-composer-update
pdf-composer-update:
	@docker run --rm -v `pwd`/service-pdf/:/app/ composer:${COMPOSER_VERSION} composer update $(PACKAGE) --prefer-dist --no-interaction --no-scripts

# use make pdf-composer-why PACKAGE=symfony\/validator
.PHONY: pdf-composer-why
pdf-composer-why:
	@docker run --rm -v `pwd`/service-pdf/:/app/ composer:${COMPOSER_VERSION} composer why $(PACKAGE)

# ------- Composer shared ----- #
.PHONY: shared-composer-install
shared-composer-install:
	@docker compose run -T --rm composer-shared install --prefer-dist --no-interaction --no-scripts

.PHONY: all-composer-install
all-composer-install:
	${MAKE} -j front-composer-install pdf-composer-install api-composer-install admin-composer-install shared-composer-install

.PHONY: dc-up
dc-up: all-composer-install ecrlogin
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_APP_VERSION=${APP_VERSION}; \
	docker compose build --build-arg ENABLE_XDEBUG=0 front-app admin-app api-app pdf-app mock-cognito; \
	docker compose up -d --remove-orphans
	$(info ${YELLOW}starting asset watcher for service-front...${RESET})
	docker compose run --rm npm-front install
	docker compose run --rm npm-front run watch

.PHONY: dc-up-debug
dc-up-debug: all-composer-install ecrlogin
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	export OPG_LPA_FRONT_OS_PLACES_HUB_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_APP_VERSION=${APP_VERSION}; \
	docker compose build front-app admin-app api-app pdf-app mock-cognito; \
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
	@docker rmi lpa-front-web || true
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
	@docker compose restart front-web api-web admin-web front-ssl admin-ssl
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
ifdef TESTFILE
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/service-front/build/coverage:/app/build/coverage front-app-test vendor/bin/phpunit $(TESTFILE)
else
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/service-front/build/coverage:/app/build/coverage front-app-test vendor/bin/phpunit
endif

.PHONY: dc-admin-unit-tests
dc-admin-unit-tests:
ifdef TESTFILE
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/service-admin/build/coverage:/app/build/coverage admin-app-test /app/vendor/bin/phpunit $(TESTFILE)
else
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/service-admin/build/coverage:/app/build/coverage admin-app-test /app/vendor/bin/phpunit
endif

.PHONY: dc-api-unit-tests
dc-api-unit-tests:
ifdef TESTFILE
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/service-api/build/coverage:/app/build/coverage api-app-test /app/vendor/bin/phpunit $(TESTFILE)
else
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/service-api/build/coverage:/app/build/coverage api-app-test /app/vendor/bin/phpunit
endif

.PHONY: dc-pdf-unit-tests
dc-pdf-unit-tests:
ifdef TESTFILE
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/service-pdf/build/coverage:/app/build/coverage pdf-app-test /app/vendor/bin/phpunit $(TESTFILE)
else
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/service-pdf/build/coverage:/app/build/coverage pdf-app-test /app/vendor/bin/phpunit
endif

.PHONY: dc-shared-unit-tests
dc-shared-unit-tests:
ifdef TESTFILE
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/shared/build/coverage:/app/build/coverage shared-test /shared/vendor/bin/phpunit $(TESTFILE)
else
	@docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm --env XDEBUG_MODE=coverage --no-deps -v `pwd`/shared/build/coverage:/app/build/coverage shared-test /shared/vendor/bin/phpunit /shared/module/MakeShared/tests
endif

.PHONY: dc-unit-tests
dc-unit-tests: dc-front-unit-tests dc-admin-unit-tests dc-api-unit-tests dc-pdf-unit-tests dc-shared-unit-tests

.PHONY: dc-front-psalm
dc-front-psalm:
	@docker compose -f docker-compose.yml run --build --rm --no-deps front-app-test vendor/bin/psalm --no-cache --force-jit

.PHONY: dc-admin-psalm
dc-admin-psalm:
	@docker compose -f docker-compose.yml run --build --rm --no-deps admin-app-test vendor/bin/psalm --no-cache --force-jit

.PHONY: dc-api-psalm
dc-api-psalm:
	@docker compose -f docker-compose.yml run --build --rm --no-deps api-app-test vendor/bin/psalm --no-cache --force-jit

.PHONY: dc-pdf-psalm
dc-pdf-psalm:
	@docker compose -f docker-compose.yml run --build --rm --no-deps pdf-app-test vendor/bin/psalm --no-cache --force-jit

.PHONY: dc-shared-psalm
dc-shared-psalm:
	@docker compose -f docker-compose.yml run --build --rm --no-deps shared-test vendor/bin/psalm --no-cache --force-jit

.PHONY: dc-psalm
dc-psalm: dc-front-psalm dc-admin-psalm dc-api-psalm dc-pdf-psalm dc-shared-psalm

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

.PHONY: _cypress-prepare-dirs
_cypress-prepare-dirs:
	mkdir -p cypress/screenshots cypress/regressions/diff cypress/regressions/baseline cypress/downloads
	chmod -R a+w cypress/screenshots cypress/regressions cypress/downloads

.PHONY: cypress-open
cypress-open: npm-install python-api-venv
	CYPRESS_userNumber=`python3 cypress/user_number.py` CYPRESS_baseUrl="https://localhost:7002" \
		CYPRESS_adminUrl="https://localhost:7003" ./node_modules/.bin/cypress open \
		--project ./ -e stepDefinitions="cypress/e2e/common/*.js"

# Provide name of the spec file (assuming it is in cypress/e2e/) e.g. cypress-run-spec SPEC=Admin.feature
# Note that the first -e is an argument to docker compose run and the second an argument to cypress run, so these need to be positioned exactly as they are
.PHONY: cypress-run-spec
cypress-run-spec: _cypress-prepare-dirs
	docker compose run --rm -v $(CURDIR)/cypress/screenshots:/app/cypress/screenshots -e CYPRESS_userNumber=`python3 cypress/user_number.py` -e CYPRESS_screenshotOnRunFailure=true cypress --spec cypress/e2e/${SPEC} -e stepDefinitions="/app/cypress/e2e/common/*.js"

# This should be used in the form : make cypress-run-tags tags=@Signup. This is mainly used by CI, its normally more convenient locally to use cypress-run-spec
# Note that the first -e is an argument to docker compose run and the second an argument to cypress run, so these need to be positioned exactly as they are
.PHONY: cypress-run-tags
cypress-run-tags: _cypress-prepare-dirs
	docker compose run --rm -v $(CURDIR)/cypress/screenshots:/app/cypress/screenshots -e CYPRESS_userNumber=`python3 cypress/user_number.py` -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",tags="${tags}"

# Creates and runs stitched test suites for visual regression testing.
.PHONY: cypress-run-stitched-suites
cypress-run-stitched-suites: _cypress-prepare-dirs
	@pushd cypress && python3 stitch.py && popd
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	CYPRESS_userNumber=`python3 cypress/user_number.py` && \
	docker compose run --rm -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/regressions:/app/cypress/regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false --expose visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",tags="@Signup" && \
	docker compose run --rm -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/regressions:/app/cypress/regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false --expose visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",tags="@StitchedHW or @StitchedPF or @StitchedClone"

# Runs the "remaining" cypress tests - everything not covered by stitched suites or signup.
# Mirrors the exclusion expression used in CI (workflow_merge_queue.yml cypress_tests_Remaining),
# plus @Admin which requires cross-origin admin-ssl navigation not supported locally.
.PHONY: cypress-run-remaining
cypress-run-remaining:
	@${MAKE} cypress-run-tags tags="not @Signup and not @PartOfStitchedRun and not @StitchedHW and not @StitchedPF and not @StitchedClone and not @CorrespondentReuse and not @SignupIncluded and not @AdminSystemMessage and not @CheckoutPaymentGateway and not @Ping and not @Admin"

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
_cypress-run-baseline-suite: _cypress-prepare-dirs
	$(info ${YELLOW}exporting secrets from aws secrets manager. you will be prompted for a password${RESET})
	@export OPG_LPA_API_NOTIFY_API_KEY=${NOTIFY}; \
	CYPRESS_userNumber=`python3 cypress/user_number.py` && \
	docker compose run --rm -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/regressions:/app/cypress/regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false --expose updateBaseline=true,visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",tags="@Signup" && \
	docker compose run --rm -v $${PWD}/cypress/screenshots:/app/cypress/screenshots -v $${PWD}/cypress/regressions:/app/cypress/regressions -e CYPRESS_userNumber=$$CYPRESS_userNumber -e CYPRESS_NO_COMMAND_LOG=1 -e CYPRESS_numTestsKeptInMemory=1 -e CYPRESS_screenshotOnRunFailure=true cypress --headless --config video=false --expose updateBaseline=true,visualRegressionEnabled=true -e stepDefinitions="/app/cypress/e2e/common/*.js",filterSpecs="true",GLOB="cypress/e2e/**/*.feature",CI="True",tags="${SUITE_TAG}"

# Replicates CI cypress runs locally to ensure visual regression test baseline images use the same user to keep
# consistent page dimensions and LPA data for each stitched suite.
.PHONY: cypress-update-all-baselines
cypress-update-all-baselines: _cypress-stitch
	@${MAKE} _cypress-run-baseline-suite SUITE_TAG=@StitchedHW
	@${MAKE} _cypress-run-baseline-suite SUITE_TAG=@StitchedPF
	@${MAKE} _cypress-run-baseline-suite SUITE_TAG=@StitchedClone

.PHONY: dc-phpcs-fix
dc-phpcs-fix:
	docker compose build phpcs
	docker compose run --rm --no-deps -q phpcs

.PHONY: dc-phpcs-check
dc-phpcs-check:
	mkdir -p phpcs/output && chmod a+w phpcs/output
	docker compose build phpcs
	docker compose run --rm --no-deps --entrypoint "./vendor/bin/phpcs --standard=/app/config/phpcs.xml.dist" phpcs --basepath=/app --report=full --report-checkstyle=/app/output/phpcs-report.xml

.PHONY: dc-clear-cache
dc-clear-cache:
	docker compose exec admin-app rm -f /tmp/config-cache-opg-lpa-admin.php
	docker compose exec front-app rm -f /tmp/config-cache.php
	docker compose exec front-app rm -rf /tmp/twig_cache
	docker compose exec api-app rm -f /app/tmp/config-cache-opg-lpa-api.php

# Use after Dockerfile changes or when env vars aren't being picked up.
.PHONY: reset-admin
reset-admin:
	docker compose up -d --force-recreate --renew-anon-volumes admin-app
	@${MAKE} dc-restart-web

# Use after Dockerfile changes or when env vars aren't being picked up.
.PHONY: reset-front-app
reset-front-app:
	docker compose up -d --force-recreate --renew-anon-volumes front-app
	@${MAKE} dc-restart-web

# Re-run the non-live seeding scripts against the running dev stack, truncating
# and re-populating the test users/applications/feedback/deletion-log tables.
.PHONY: dc-reseed
dc-reseed:
	@docker compose run --rm seeding

.PHONY: update-secrets-baseline
update-secrets-baseline:
	detect-secrets scan --baseline .secrets.baseline

.PHONY: psql
psql:
	@docker exec -it lpa-postgres psql --username=lpauser --dbname=lpadb --pset=expanded=auto

block-ips-tests: ##@unit-tests Run the unit tests for IP blocking lambda.
	docker compose -f docker-compose.commands.yml up block-ips-tests --build
