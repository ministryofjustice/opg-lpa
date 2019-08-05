# Work notes
Notes, remarks and reminders for the migration of stack resource management from ansible to terraform.

## production specifics
  - rds password
  - production has route53 configuration for sendgrid in hosted zone lastingpowerofattorney.service.gov.uk.
      I think this could be placed in the vpc level 

## Tech Debt
### Build
Run unit tests before push?

### Account
in credentials, replace local.account_id with a variable


### Environment
nginx server names needs to be updated or possible removed
accounts don't have permission to ecr
establish what env vars are actually required



## Secrets
This is a list of the secrets currently set in the ASG containers
Some may no longer be needed
- OPG_LPA_API_NOTIFY_API_KEY
- OPG_LPA_COMMON_ACCOUNT_CLEANUP_NOTIFICATION_RECIPIENTS
- OPG_LPA_COMMON_ADMIN_ACCOUNTS
- OPG_LPA_FRONT_SESSION_ENCRYPTION_KEY
- OPG_LPA_FRONT_SESSION_ENCRYPTION_KEYS
- OPG_LPA_ADMIN_JWT_SECRET
- OPG_LPA_FRONT_EMAIL_SENDGRID_WEBHOOK_TOKEN
- OPG_LPA_FRONT_EMAIL_SENDGRID_API_KEY
- OPG_LPA_FRONT_NGINX_AUTH
- OPG_LPA_FRONT_GOV_PAY_KEY
- OPG_LPA_FRONT_ORDNANCE_SURVEY_LICENSE_KEY
- OPG_LPA_PDF_ENCRYPTION_KEY_QUEUE
- OPG_LPA_PDF_ENCRYPTION_KEY_DOCUMENT
- OPG_LPA_PDF_OWNER_PASSWORD


## Collapse of the Repositories
# collapse these
opg-lpa-front
opg-lpa-admin
opg-lpa-api
opg-lpa-pdf
opg-lpa-deploy (gitlab)
https://gitlab.service.opg.digital/lpa/lpa-tests <modify to use env vars for secrets

# archive thses
opg-lpa-docker
opg-lpa-docker-ecr

# keep these separate
opg-lpa-maintenance
opg-lpa-datamodels < pulled in by composer
opg-lpa-logger