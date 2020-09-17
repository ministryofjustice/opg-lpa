SHELL := '/bin/bash'
SENDGRID := $(shell aws-vault exec lpa-dev-operator -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_email_sendgrid_api_key | jq -r .'SecretString')
GOVPAY := $(shell aws-vault exec lpa-dev-operator -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_gov_pay_key | jq -r .'SecretString')
ORDNANCESURVEY := $(shell aws-vault exec lpa-dev-operator -- aws secretsmanager get-secret-value --secret-id development/opg_lpa_front_ordnance_survey_license_key | jq -r .'SecretString')

.PHONY: all
all:
	@${MAKE} up

.PHONY: run
run:
	export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
		docker-compose run front-composer

	export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
		docker-compose run admin-composer

	export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
		docker-compose run api-composer

	export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
		docker-compose run pdf-composer

.PHONY: up
up:
	export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
		docker-compose up

.PHONY: build
build:
	export OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY=${SENDGRID}; \
	export OPG_LPA_FRONT_GOV_PAY_KEY=${GOVPAY}; \
	export OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY=${ORDNANCESURVEY}; \
		docker-compose build

.PHONY: tests
tests:
	docker-compose run front-app /app/vendor/bin/phpunit
	docker-compose run admin-app /app/vendor/bin/phpunit
	docker-compose run api-app /app/vendor/bin/phpunit
	docker-compose run pdf-app /app/vendor/bin/phpunit
