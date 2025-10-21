# AWS Aurora major version upgrades using blue/green deployments (console)

This document sets out how to perform a major version upgrade of PostgreSql using Aurora blue/green deployments.

This runbook is based on the following AWS documentation;

- [Creating blue/green deployments](https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/blue-green-deployments-creating.html)
- [Switching blue/green deployments](https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/blue-green-deployments-switching.html)
- [Deleting blue/green deployments](https://docs.aws.amazon.com/AmazonRDS/latest/AuroraUserGuide/blue-green-deployments-deleting.html)

This guide focusses on using the AWS console for this activity. This guide and images reference a development environment, but this guide works for any environment.

## Prerequisites

You must be able to assume the correct roles for this activity.

Development and Preproduction requires the `breakglass` role.
Production requires the `data-access` role.

## Use terraform to create new parameter groups

Create a new db parameter group in `terraform/environment/modules/environment/rds.tf`

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

Merge these changes to the main branch using a pull request.

## Creating a blue/green deployment

1. Sign in to the AWS console, assume the correct role into the account requiring the upgrade, navigate to `Aurora and RDS`

1. Choose the Aurora cluster to upgrade, click `Actions`, then `Create blue/gree deployment`

1. In the Create screen
    - name the deployment (optional)
    - select the engine version to upgrade to
    - select the postgres15 db cluster parameter group terraform created
    - choose the default db parameter group for postgresql15
    - tick the box to fix the blue parameter group

1. Confirm the reboot and click `Continue`

1. Review and confirm the deployment, and click `Reboot and create`

## Switching to green deployment

1. Choose the blue/green deployment, click `Actions`, then `Switch over`
