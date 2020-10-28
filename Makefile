SHELL := '/bin/bash'
SENDGRID := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_email_sendgrid_api_key | jq -r .'SecretString')
GOVPAY := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_gov_pay_key | jq -r .'SecretString')
ORDNANCESURVEY := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_ordnance_survey_license_key | jq -r .'SecretString')
ADMIN_USERS := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_common_admin_accounts | jq -r .'SecretString')
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
	docker-compose run front-composer

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run admin-composer

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run api-composer

	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
    docker-compose run pdf-composer

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
	docker-compose build


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
	docker-compose down --remove-orphans; \
	docker system prune -f --volumes; \
	docker rmi lpa-pdf-app; \
	docker rmi lpa-admin-web; \
	docker rmi lpa-admin-app; \
	docker rmi lpa-api-web; \
	docker rmi lpa-api-app; \
	docker rmi lpa-front-web; \
	docker rmi lpa-front-app; \
	docker rmi seeding; \
	docker rmi opg-lpa_local-config; \
	rm -fr ./service-admin/vendor; \
    rm -fr ./service-api/vendor; \
    rm -fr ./service-front/node_modules/parse-json/vendor; \
    rm -fr ./service-front/node_modules/govuk_frontend_toolkit/javascripts/vendor; \
    rm -fr ./service-front/public/assets/v2/js/vendor; \
    rm -fr ./service-front/vendor; \
    rm -fr ./service-pdf/vendor; \
	docker-compose build --no-cache

.PHONY: dc-down
dc-down:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose down --remove-orphans

.PHONY: dc-unit-tests
dc-unit-tests:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose run front-app /app/vendor/bin/phpunit

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
