## Failover to eu-west-2

## Context

We need to be able to have the option to switch over Make an LPA to a different region should there be an outage in it's current region.

This is to improve the resilience of the service, whilst meeting business agreed Recovery Time Objectives (RTO) and Recovery Point Objectives (RPO).

We considered a range of options from pure backup restore, through to full standby. It was decided that a backup restore being the most cost effective approach for this service.

We have attempted to build the process such that this required minimal manual intervention.

This runbook documents the approach that can be taken to ensure the service stands up in a different region (eu-west-2 currently) to the current live region (eu-west-1).

The runbook below outlines the steps to be taken. It is important to follow this in the correct order. It is recommended that this is done from a local machine in order to provision these resources, for speed.

## Caveats & Prerequesites

- Backup plans are not changed by this procedure
- Slack alerts and pagerduty alarms are not changed
- Breakglass access is required for the make a lpa AWS accounts, and the identity, management and backup accounts.

! Consider enabling maintenance mode if possible before starting this procedure

### Approximate time to restore service

During initial testing the process took approvimately 45 minutes to 1 hour. This is likely increase on a production Database, due to its size.

### High level approach

requires 4 major tasks:

[1. Provision region level resources](#1-provision-region-level-resources)
[2. Provision environment level resources](#2-provision-environment-level-resources)
[3. Restore Database](#3-restore-database)
[4. Redirect DNS](#4-provision-environment-level-resources)
[5. Test the service](#5-test-the-service)
[6. Set up CI ingress and commit changes](#6-set-up-ci-ingress-and-commit-changes)
[7. Disable maintenance mode](#7. Disable maintenance mode)

### Notes on replacement values

Use the following as replacement values, depending on the scenario:

- `<account_name>` refers to relevant aws account name e.g. `development, preproduction, or production`
- `<prod_or_non_prod>` refers to type of alert i.e `Production` for production alerts, otherwise `Non-Production` for Development or Preproduction alerts.
- `<my-workspace>` refers to the ephemeral environment workspace to select in development account; `preproduction` or `production` in those accounts respectively.

### 0. Enable Maintenance mode
It's recommended that maintenance mode be enabled if it is possible
see docs/runbooks/maintenance_mode/README.md for instructions

### 1. Provision region level resources

1. Go to `terraform/region` folder.
2. Set the workspace value .envrc:`export TF_workspace=<account_name>`.
3. Set `breakglass` role in .envrc: `export TF_VAR_default_role=breakglass`.
4. run `direnv allow` if it doesn’t autorun `tfswitch`.
6. In the `terraform.tfvars.json` under the "accounts" -> "<account_name>", change `dr_enabled` value to `true` and save.
5. Perform an initialisation of the workspace `aws-exec vault identity — terraform init`.
7. Run a plan on region: `aws-vault exec identity — terraform plan`.
8. Check contents of the plan. Things to look out for include:
   - no destruction of resources in eu-west-1
   - approx 80+ created resources in eu-west-2.
9. Run an apply: `aws-vault exec identity — terraform apply` and enter `yes`.

## 2. Provision environment level resources

1. Go to the `terraform/environment` folder, and enter credentials as requested for AWS.
3. Set `breakglass` role in .envrc: `export TF_VAR_default_role=breakglass`.
2. List the workspaces to find the environment to failover `aws-vault exec identity -- terraform workspace list`.
3. Select your workspace from the list and switch to it: `aws-vault exec identity --  terraform workspace select <my-workspace>`.
4. perform an initialisation of the workspace: `aws-exec vault identity — terraform init`.

5. In the `terraform.tfvars.json’ under the environments" -> “<environment_name>", change `regions.eu-west-2.enabled` value to true and save the file.
6. Run a plan on region: `aws-vault exec identity — terraform plan`.
7. Check contents of plan. Things to look out for include:
   - no destruction of resources in eu-west-1
   - approx 140+ created resources in eu-west-2.
8. Run an apply: `aws-vault exec identity — terraform apply` and enter `yes`.

## 3. Restore Database
see docs/runbooks/aws_aurora/database_restore_procedure.md for full details on this procedure

1. Restore a cluster from a backup
2. bring restored cluster into service
3. commit changes

## 4. Redirect DNS

1. Go to the `terraform/environment` folder, and enter credentials as requested for AWS.
3. Set `breakglass` role in .envrc: `export TF_VAR_default_role=breakglass`.
2. List the workspaces to find the environment to failover `aws-vault exec identity -- terraform workspace list`.
3. Select your workspace from the list and switch to it: `aws-vault exec identity --  terraform workspace select <my-workspace>`.
4. perform an initialisation of the workspace: `aws-exec vault identity — terraform init`.
5. In the `terraform.tfvars.json’ under the environments" -> “<environment_name>", change “dr_enabled” value to true and save the file.
6. Run a plan on region: `aws-vault exec identity — terraform plan`.
7. Check contents of plan. Things to look out for include:
   - route53 records being updated to point to load balancers in eu-west-2
   - changes to the config file to show eu-west-2 resources
   - approx 3 resources changed
8. Run apply on environment, `aws-vault exec identity — terraform apply`.
9. Type yes if satisfied with the plan.
10. Run an apply: `aws-vault exec identity — terraform apply` and enter `yes`.

## 5. Test the service
1. Login to the service and perform some sanity checks. you can ask for assistance from the product owner on this.
2. Examples of tests:
    1. Register
    2. Login
    3. Update your details
    4. Create and save a basic LPA
    5. Test payment
    6. check address lookups
    7. Download a PDF

Set up is complete of the environment, but you may also want to check in these changes and push to a CI build, which the next section should help with.

## 6. Set up CI ingress and commit changes

We need to align these changes with CI so that we can perform tests and continue with development and fix on fail work.

1. In the terraform account-ingress folder, change the terraform.tfvars.json value of `dr_enabled` to true.
2. Commit this and the other `dr_enabled` flag changes made to a branch and create a PR.
3. If working on preproduction or potentially production, merge to main after approval by team.

## 7. Disable maintenance mode
see docs/runbooks/maintenance_mode/README.md for instructions
