SHELL := '/bin/bash'
SENDGRID := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_email_sendgrid_api_key | jq -r .'SecretString')
GOVPAY := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_gov_pay_key | jq -r .'SecretString')
ORDNANCESURVEY := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_ordnance_survey_license_key | jq -r .'SecretString')
ADMIN_USERS := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_common_admin_accounts | jq -r .'SecretString')
MYIP := $(shell ipconfig getifaddr en0)
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
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY} ; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run front-composer | xargs -L1 echo front-composer: &

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	sleep 20; docker-compose run admin-composer | xargs -L1 echo admin-composer: &

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	sleep 20; docker-compose run api-composer | xargs -L1 echo api-composer: &

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	sleep 20; docker-compose run pdf-composer | xargs -L1 echo pdf-composer: 

.PHONY: dc-up
dc-up:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose up

.PHONY: dc-build
dc-build:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose build


# remove docker containers, volumes, images left by existing system, remove vendor folders, rebuild everything
# with no-cache
# this leaves things in a state where make dc-run is needed again before starting back up
.PHONY: dc-build-clean
dc-build-clean:
	@${MAKE} dc-down
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker system prune -f --volumes; \
	docker rmi lpa-pdf-app || true; \
	docker rmi lpa-admin-web || true; \
	docker rmi lpa-admin-app || true; \
	docker rmi lpa-api-web || true; \
	docker rmi lpa-api-app || true; \
	docker rmi lpa-front-web || true; \
	docker rmi lpa-front-app || true; \
	docker rmi seeding || true; \
	docker rmi opg-lpa_local-config; \
	rm -fr ./service-admin/vendor; \
	rm -fr ./service-api/vendor; \
	rm -fr ./service-front/node_modules/parse-json/vendor; \
	rm -fr ./service-front/node_modules/govuk_frontend_toolkit/javascripts/vendor; \
	rm -fr ./service-front/public/assets/v2/js/vendor; \
	rm -fr ./service-front/vendor; \
	rm -fr ./service-pdf/vendor; \
	COMPOSE_DOCKER_CLI_BUILD=1 DOCKER_BUILDKIT=1 docker-compose build --no-cache

# only reset the front container - uesful for quick reset when only been working on front component
.PHONY: reset-front
reset-front:
	@${MAKE} dc-down
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker system prune -f --volumes; \
	docker rmi lpa-front-web || true; \
	docker rmi lpa-front-app || true; \
	rm -fr ./service-front/node_modules/parse-json/vendor; \
	rm -fr ./service-front/node_modules/govuk_frontend_toolkit/javascripts/vendor; \
	rm -fr ./service-front/public/assets/v2/js/vendor; \
	rm -fr ./service-front/vendor; \
	docker-compose build --no-cache front-web 
	docker-compose build --no-cache front-app
	docker-compose run front-composer

.PHONY: dc-down
dc-down:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose down --remove-orphans

.PHONY: dc-front-unit-tests
dc-front-unit-tests:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run front-app /app/vendor/bin/phpunit

.PHONY: dc-unit-tests
dc-unit-tests:
	@${MAKE} dc-front-unit-tests

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run admin-app /app/vendor/bin/phpunit

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run api-app /app/vendor/bin/phpunit

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run pdf-app /app/vendor/bin/phpunit

.PHONY: functional-local
functional-local:
	docker build -f ./tests/Dockerfile  -t casperjs:latest .; \
	aws-vault exec identity -- docker run -it -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY -e AWS_SESSION_TOKEN -e "BASE_DOMAIN=localhost:7002" --network="host" --rm casperjs:latest ./start.sh 'tests/'

.PHONY: cypress-local
cypress-local:
	docker build --no-cache -f ./cypress/Dockerfile  -t cypress:latest .; \
	docker run -it -e "CYPRESS_baseUrl=https://localhost:7002" --network="host" --rm cypress:latest cypress run --spec cypress/integration/home.spec.js
	docker run -it -e "CYPRESS_baseUrl=https://localhost:7002" --network="host" --rm cypress:latest cypress run --spec cypress/integration/basic_login.spec.js
	docker run -it -e "CYPRESS_baseUrl=https://localhost:7002" --network="host" --rm cypress:latest cypress run --spec cypress/integration/BasicLogin.feature

.PHONY: cypress-gui-local
cypress-gui-local:
	docker build --no-cache -f ./cypress/Dockerfile  -t cypress:latest .; \
		docker run -it -e "DISPLAY=${MYIP}:0" -e "CYPRESS_VIDEO=true" -e "CYPRESS_baseUrl=https://localhost:7002"  -v ${PWD}/cypress:/app/cypress --entrypoint cypress --network="host" --rm cypress:latest open --project /app
