# Seeding non-live environments with test data

This document sets out how to dump and insert database records from an AWS environment for the purposes of seeding test data.

In a future revision, these instructions will show how to do this with local environments and across AWS and local environments.

**These instructions should not be used on Production!**

Create an account and LPAs on an AWS environment, taking them as far as you need through the process.

These will be the records we will dump from the database.

## Capturing data from an environment using pg_dump

First, establish a connection to an AWS environment database using the instructions in docs/runbooks/terminal_access.md.

Connect to the database

``` bash
PGPASSWORD=${DB_PASSWORD} psql ${API_OPTS} api2
```

Dump users table to file

``` bash
PGPASSWORD=${DB_PASSWORD} pg_dump ${API_OPTS} \
  --table="users" \
  --data-only \
  --column-inserts \
  api2 > seed_test_users.sql
```

Dump applications table to file

``` bash
PGPASSWORD=${DB_PASSWORD} pg_dump ${API_OPTS} \
  --table="applications" \
  --data-only \
  --column-inserts \
  api2 > seed_test_applications.sql
```

If you review the contents of these .sql files you will see a section with insert commands.

``` sql
-- Test data, intentionally commited to repository
INSERT INTO public.applications (id, "user", "updatedAt", "startedAt", "createdAt", "completedAt", "lockedAt", locked, "whoAreYouAnswered", seed, "repeatCaseNumber", document, payment, metadata, search) VALUES (33718377316, '082347fe0f7da026fa6187fc00b05c55', '2020-01-21 15:18:44.998797+00', '2020-01-21 15:16:28.827809+00', '2020-01-21 15:18:39.005026+00', '2020-01-21 15:18:58.194165+00', '2020-01-21 15:18:58.188918+00', true, true, NULL, NULL, '{"type": "property-and-financial", "donor": {"dob": {"date": "1982-11-28T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Test", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "canSign": true, "otherNames": ""}, "preference": "", "instruction": "", "correspondent": {"who": "donor", "name": {"last": "User", "first": "Test", "title": "Mr"}, "email": {"address": "test_user@digital.justice.gov.uk"}, "phone": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}, "company": null, "contactByPost": false, "contactInWelsh": false, "contactDetailsEnteredManually": null}, "peopleToNotify": [{"id": 1, "name": {"last": "Person", "first": "Notifiable", "title": "Mr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "primaryAttorneys": [{"id": 1, "dob": {"date": "1985-01-07T00:00:00.000000+0000"}, "name": {"last": "User", "first": "Celeste", "title": "Miss"}, "type": "human", "email": null, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}], "whoIsRegistering": "donor", "certificateProvider": {"name": {"last": "User", "first": "Francest", "title": "Dr"}, "address": {"address1": "THE OFFICE OF THE PUBLIC GUARDIAN", "address2": "THE AXIS", "address3": "10 HOLLIDAY STREET, BIRMINGHAM", "postcode": "B1 1TF"}}, "replacementAttorneys": [], "primaryAttorneyDecisions": {"how": null, "when": "now", "howDetails": null, "canSustainLife": null}, "replacementAttorneyDecisions": null}', '{"date": null, "email": null, "amount": 82, "method": "cheque", "reference": null, "gatewayReference": null, "reducedFeeLowIncome": null, "reducedFeeAwardedDamages": null, "reducedFeeUniversalCredit": null, "reducedFeeReceivesBenefits": null}', '{"instruction-confirmed": true, "people-to-notify-confirmed": true, "repeat-application-confirmed": true, "replacement-attorneys-confirmed": true}', 'Mr Test User');
```

You can copy the `INSERT INTO` sections from the users and applications table dumps into their respective files in this repository at scripts/non_live_seeding/seed_users.sql and scripts/non_live_seeding/seed_applications.sql.

Commit these changes to the repository and the next run of the CI/CD pipeline will seed the updated results into the environment.

## Seeding a local environment

You must be connected to the MoJ VPN before beginning these instructions.

## Seeding an environment using the pipeline

The CircleCI pipeline will run a seeding task after the environment has been built using the scripts in scripts/non_live_seeding.

