SHELL := '/bin/bash'
SENDGRID := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_email_sendgrid_api_key | jq -r .'SecretString')
GOVPAY := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_gov_pay_key | jq -r .'SecretString')
ORDNANCESURVEY := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_ordnance_survey_license_key | jq -r .'SecretString')
ADMIN_USERS := $(shell aws-vault exec moj-lpa-dev -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_common_admin_accounts | jq -r .'SecretString')
.PHONY: all
all:
	@${MAKE} dc-up

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


.PHONY: dc-build-clean
dc-build-clean:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose build --no-cache

.PHONY: dc-down
dc-down:
	@export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
	export OPG_LPA_COMMON_ADMIN_ACCOUNTS=${ADMIN_USERS}; \
	docker-compose down

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
