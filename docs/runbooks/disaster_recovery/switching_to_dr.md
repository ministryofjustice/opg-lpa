
# Failover to eu-west-2

## Context

We need to be able to have the option to switch over Make an LPA to a different region should there be an outage in it's current region.

This is to improve the resilience of the service, whilst meeting business agreed Recovery Time Objectives (RTO) and Recovery Point Objectives (RPO).

We considered a range of options from pure backup restore, through to full standby. It was decided that a backup restore being the most cost effective approach for this service.

We have attempted to build the process such that this required minimal manual intervention.

This runbook documents the approach that can be taken to ensure the service stands up in a different region (eu-west-2 currently) to the current live region (eu-west-1).

## Prerequisites

you need to have familiarity with:

- PagerDuty
- Terraform
- Bash Scripting

## Caveats and improvements

 At this stage the switch over has the following caveats and  improvements needed, which can be removed from here when the team are happy that these are satisfied:

1. **This approach has only been tested in the Development account**. this will need to be tested also in Preproduction, with a view to be tested in Production, once a failback strategy is in place also.

2. **We are yet to workout a failback process**. This will need to be addressed in a separate ticket already raised. The current assumptions are:
   1. That we will stay in the alternative region for longer.
   2. That the original region becomes DR.
   3. We can switch over terraform specified resources, when the original location becomes available again, using state moves and tweaks to the terraform.
   4. The above assumptions are subject to change.

3. **This process is an initial version**. As such will be subject to change based on potential optimisations, and upgrades to the DB infrastructure e.g. Aurora Serverless.

4. **The Production and Preproduction DB instance type is deprecated**. the db.m3.medium instance type is being removed from use in april 2023, and is no longer available in selection of DB’s for eu-west-2. the DR region has been coded to upgrade to db.m5.large as this is the minimum available fro general purpose use.

5. **The management account Elastic Container Registry (ECR) is not currently replicated fully**. It currently only replicates lambdas, since ECR containers are cross region accessible. If the ECR goes down in the primary we will need to:
   1. Set up org infra to push containers to the alternative region.
   2. Tweak the build scripts to push containers to the alternative region ECR in management account.
   3. This will be improved once decisions are made about replicating Container images to a secondary region.

6. **WebOps support is recommended during DR**. Though not neccesary, it is strongly advised to reach out to a webops engineer when running this runbook.

## Runbook

The runbook below outlines the steps to be taken. It is important to follow this in the correct order. It is recommended that this is done from a local machine in order to provision these resources, for speed.

### Approximate time to restore service

During initial testing the process took approvimately 45 minutes to 1 hour. This is likely increase on a production Database, due to its size.

### High level approach

requires 4 major tasks:

- [Failover to eu-west-2](#failover-to-eu-west-2)
  - [Context](#context)
  - [Prerequisites](#prerequisites)
  - [Caveats and improvements](#caveats-and-improvements)
  - [Runbook](#runbook)
    - [Approximate time to restore service](#approximate-time-to-restore-service)
    - [High level approach](#high-level-approach)
    - [Notes on replacement values](#notes-on-replacement-values)
    - [1. Provision region level resources](#1-provision-region-level-resources)
    - [2. Pagerduty Setup for DB alerts](#2-pagerduty-setup-for-db-alerts)
  - [3. Provision environment level resources](#3-provision-environment-level-resources)
  - [4. Set up CI ingress and commit changes](#4-set-up-ci-ingress-and-commit-changes)

### Notes on replacement values

Use the following as replacement values, depending on the scenario:

- `<account_name>` refers to relevant aws account name e.g. `development, preproduction, or production`
- `<prod_or_non_prod>` refers to type of alert i.e `Production` for production alerts, otherwise `Non-Production` for Development or Preproduction alerts.
- `<my-workspace>` refers to the ephemeral environment workspace to select in development account; `preproduction` or `production` in those accounts respectively.

### 1. Provision region level resources

1. In the opg-lpa repository folder, run `direnv allow` if it doesn’t autorun `tfswitch`.
2. Go to `terraform/region` folder.
3. Select workspace at region level e.g.:`export TF_workspace=<account_name>`.
4. Set `breakglass` role; this is only for non development accounts: `export TF_VAR_default_role=breakglass`.
5. Perform an initialisation of the workspace  `aws-exec vault identity — terraform init`.
6. In the `terraform.tfvars.json’ under the “accounts" -> “<account_name>", change “dr_enabled” value to true and save.
7. Run a plan on region: `aws-vault exec identity — terraform plan`.
8. Check contents of the plan.
9. Run an apply: `aws-vault exec identity — terraform apply` and enter `yes`.
10. In the #opg-lpa-live-db-alerts slack channel A db events subscription alert in  with `custom event transform` in the title will appear.
11. Click through the title before the teraform apply times out within 1 minute
    1. The incident will open in pagerduty - in the `rawBody` section of the incident click the `subscribeUrl` value to confirm subscription; there is no way around this being done automatically at this stage.
    2. if this was successful then it will complete the application.
    3. if the apply gives you a timeout error, repeat step 9 - 11.

### 2. Pagerduty Setup for DB alerts

1. Find the region alerts integration inside of PagerDuty.
2. Go to the Services top tab in the Pagerduty app page and look in the service directory for `<prod_or_on_prod> Make a Lasting Power of Attorney Database Alerts`.
3. In the integrations tab look for `<account_name> eu-west-1 Region DB Alerts` and click the cog icon.
4. Click on `Edit Integration` in the top right of the page.
5. Select all of `The code you want to execute` value and copy this over.
6. Click `cancel` to close.
7. Click on the `<prod_or_non_prod> Make a Lasting Power of Attorney Database Alerts` breacrumb to go back
8. In the integrations tab look for `<account_name> eu-west-2 Region DB Alerts` and click the cog icon.
9. Click on edit integration on the top right of the page.
10. In the code you want to execute value, paste to replace the previous code.
11. Set Debg mode to Disabled (or leave as is in case of issues).
12. Click `save changes`.

The region level set up is complete at this point.

## 3. Provision environment level resources

1. Go to the `terraform/environment` folder, and enter credentials as requested for AWS.
2. List the workspaces to find the environment you are interested in by running `aws-vault exec identity -- terraform workspace list`.
3. Select your workspace from the list and switch to it:  `aws-vault exec identity --  terraform workspace select <my-workspace>`.
4. perform an initialisation of the workspace:  `aws-exec vault identity — terraform init`.
5. In the `terraform.tfvars.json’ under the “accounts" -> “development", change “dr_enabled” value to true and save the file.
6. Run a plan on region: `aws-vault exec identity — terraform plan`.
7. Check contents of plan.
8. Run apply on region, `aws-vault exec identity — terraform plan`.
9. Type yes if satisfied with the plan.
10. Run an apply: `aws-vault exec identity — terraform apply` and enter `yes`.
11. Login to the service and perform some sanity checks. you can ask for assistance from the product owner on this.
12. Examples of tests:
    1. Register
    2. Login
    3. Update your details
    4. Create and save a basic LPA
    5. Test payment
    6. check address lookups
    7. Download a PDF

1Set up is complete of the environment, but you may also want to check in these changes and push to a CI build.

## 4. Set up CI ingress and commit changes

We need to align these changes with CI so that we can perform tests and continue with development and fix on fail work.

1. In the terraform account-ingress folder, change the terraform.tfvars.json value of `dr_enabled` to true.
2. Commit this and the other `dr_enabled` flag changes made to a branch and create a PR.
3. If working on preproduction or potentially production, merge to main after approval by team.
