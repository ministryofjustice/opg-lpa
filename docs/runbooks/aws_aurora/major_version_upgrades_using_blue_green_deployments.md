# AWS Aurora major version upgrades using blue/green deployments (console)

This document sets out how to perform a major version upgrade of PostgreSql using Aurora blue/green deployments.

This runbook is based on the following AWS documentation;

- [Creating blue/green deployments](https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/blue-green-deployments-creating.html)
- [Switching blue/green deployments](https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/blue-green-deployments-switching.html)
- [Deleting blue/green deployments](https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/blue-green-deployments-deleting.html)

This guide focusses on using the AWS console to upgrade a develpment environment from postgres 14 to 15. This guide works for any environment.

## Prerequisites

You must be able to assume the correct roles for this activity.

Development and Preproduction requires the `breakglass` role.
Production requires the `data-access` role.

## Update terraform to support an upgrade

1. Update the instance and cluster configuration in `terraform/environment/modules/environment/rds.tf` support a switchover

Example

Update the conditions used to select the db parameter group based on environment's `var.account.psql_parameter_group_family` value

```hcl
resource "aws_db_instance" "api" {
  count                               = var.account.always_on ? 1 : 0
  identifier                          = lower("api-${var.environment_name}")
  parameter_group_name                = var.account.psql_parameter_group_family == "postgres14" ? aws_db_parameter_group.postgres14-db-params.name : aws_db_parameter_group.postgres15-db-params.name
  vpc_security_group_ids              = [aws_security_group.rds-api.id]
  ...
}
```

Update the conditions used to select the cluster parameter group based on environment's `var.account.psql_parameter_group_family` value

```hcl
module "api_aurora" {
  auto_minor_version_upgrade      = true
  source                          = "./modules/aurora"
  ...
  aws_rds_cluster_parameter_group = var.account.psql_parameter_group_family == "postgres14" ? aws_rds_cluster_parameter_group.postgresql14-aurora-params.name : aws_rds_cluster_parameter_group.postgresql15-aurora-params.name
  ...
}
```

Start a pull request with this change. After the upgrade, this wil be modified and merged in.

## Creating a blue/green deployment

1. Sign in to the AWS console, assume the correct role into the account requiring the upgrade, navigate to `Aurora and RDS`

1. Choose the Aurora cluster to upgrade, click `Actions`, then `Create blue/green deployment`

1. In the Create screen
    - name the deployment (optional)
    - select the engine version to upgrade to
    - select the postgres15 db cluster parameter group terraform created
    - choose the default db parameter group for postgresql15
    - tick the box to fix the blue parameter group

1. Confirm the reboot and click `Continue`

1. Review and confirm the deployment, and click `Reboot and create`

## Switching to green deployment

Aurora blue/green deployments use switchover guardrails, checks prior to starting switchover prevent unecesseary downtime. See more at [Switchover Guardrails](https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/blue-green-deployments-switching.html#blue-green-deployments-switching-guardrails).

1. Choose the blue/green deployment, click `Actions`, then `Switch over` After the switch over, the old databases will be renamed with `old`.

## Deleting the deployment after switching over

1. Choose the blue/green deployment, click `Actions`, then `Delete`

1. In the Delete Blue/Green Deployment screen, type `delete me` to permanently delete the deployment and click `Delete`

1. Choose the old instances in the old cluster, click `Actions`, then `Delete`.

1. In the Delete instance screen, type `delete me` to permanently delete the instance and click `Delete`

1. Choose the old cluster, click `Actions`, then `Delete`

## Merge infrastructure changes into main

The last task to perform is updating terraform so that it matches the database changes made int he console.

1. Update `terraform/environment/terraform.tfvars.json` to configure an environment to use the new `psql_engine_version` and `psql_parameter_group_family`.

Example

```json
{
  "account_mapping": {
    "development": "development",
    "preproduction": "preproduction",
    "production": "production"
  },
  "accounts": {
    "development": {
      ...
      "psql_engine_version": "15",
      "psql_parameter_group_family": "postgres15",
      ...
      }
    },

```

Push this change and merge the pull request
