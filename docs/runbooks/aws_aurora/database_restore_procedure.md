# Restoring an Aurora Cluster from a backup

AWS Backup is used to schedule and manage backups for the `api` Aurora Cluster.

As with other AWS backup processes, restored backups create a new resource which must be named differently from the source resource. If the source were deleted prior to restoration this would not be an issue, but this comes with risk.

This document will walk you through how to restore a backup, and bring it into service, and how to delete the old table.

The guide is targetted at Production restoring a backup of the api cluster table, but the screenshots are from Preproduction.

This guide focusses on using the AWS console.

## Prerequisites

- You must be able to assume the correct roles for this activity.
- Development and Preproduction requires the `breakglass` role.
- Production requires the `data-access` role.
- This procedure requires pairing. This is to help validate each step is completed correctly and have more of the team comfortable with the procedure.
- It is strongly recommended that the service is put into maintenance mode to prevent users entering data that will be lost. Customer data entered since the last backup will also be lost.
- We will need to initiate a Change Freeze when we are ready to bring the restored tables into the service. During this time, any run of the path to live pipeline risks deleting the restored or outgoing tables. The freeze will help to prevent this.
- This restore procedure can take hours to perform.
- You will need the image tag currently deployed to production

## Restore a table from a backup

Sign in to the AWS Console, Assume the `breakglass` role in the Production account, and navigate to AWS Backup.

<view of the aws backups landing page>

From the menu on the left, expand My account and click on Backup Vaults.

Click on the vault named production_main_backup_vault.

<view of the backup vaults table with cursor above development main backup vault>

This will show a list of backups for that cluster that can be used as recovery points.

We will select a single backup using the `Resource ID` and `Creation time` to pick one that is appropriate, and tick it.

> You can only restore one backup at a time, so pick only one and repeat the process.

<view of the backups table, with a recovery point id selected>

At the top right of this table, click the `Actions` dropdown and choose `Restore`.

<view of the top right of the backups table actions dropdown with the cursor over the restore option>

This will open the Restore backup wizard.

<the restore backup wizard asking for a new table name to be provided, and showing indexes that will also be restored>

You must choose a new name for the table. Use the original name plus a `-` then the date of restoration in the format `YYYYMMDD`. For example `api-20251128`. This will make is easier to manage restored clusters going forward.

It is not possible to change the name of a Aurora cluster after it is created. This new name will be brought into our infrastructure as code.

Select the `Default role` as the `Restore role` and click `Restore backup`.

<view of the restore backup wizard, showing default role is selected for the restore role, and the restore backup button active.>

You’ll be taken to the `Jobs` page on the `Restore jobs` tab.

<view of the aws backups restore jobs table, showing a pending restore job in progress.>

Restore jobs can take a long time (hours) to complete.

Repeat these steps for each table that should be restored.

## Bring restored table into service

Here we will update the infrastructure as code to use the new restored table.

Ensure you are up to date with the main branch.

```sh
git checkout main
git pull
```

In terminal, navigate to the Terraform environment configuration and select the production workspace

```sh
cd terraform/environment
aws-vault exec identity -- terraform init
aws-vault exec identity -- terraform workspace select production
```

TODO: Update this when move the db to a shared resouce.

Edit the terraform/environment/.envrc file to set the TF_VAR_default_role to breakglass.

<view of the .envrc file, setting the tf_var_default_role value to breakglass>

update your environment variables from .envrc

```sh
direnv allow
```

or

```sh
source .envrc
```

Initialise the terraform configuration

```sh
aws-vault exec identity -- terraform init
```

Next, remove the Aurora cluster we are replacing from Terraform state. This stops terraform from managing it and makes way for us to import the new cluster.

```sh
aws-vault exec identity -- terraform state rm 'module.eu-west-1.module.api_aurora[0].aws_rds_cluster.cluster_serverless[0]'
aws-vault exec identity -- terraform state rm 'module.eu-west-1.module.api_aurora[0].aws_rds_cluster_instance.serverless_instances[0]'
aws-vault exec identity -- terraform state rm 'module.eu-west-1.module.api_aurora[0].aws_rds_cluster_instance.serverless_instances[1]'
aws-vault exec identity -- terraform state rm 'module.eu-west-1.module.api_aurora[0].aws_rds_cluster_instance.serverless_instances[2]'
```

Next import the restored table using the new name

```sh
aws-vault exec identity -- terraform import 'module.eu-west-1.module.api_aurora[0].aws_rds_cluster.cluster_serverless[0]' api-20251128-production
aws-vault exec identity -- terraform import 'module.eu-west-1.module.api_aurora[0].aws_rds_cluster_instance.serverless_instances[0]' api20251128-production-2
aws-vault exec identity -- terraform import 'module.eu-west-1.module.api_aurora[0].aws_rds_cluster_instance.serverless_instances[1]' api20251128-production-2
aws-vault exec identity -- terraform import 'module.eu-west-1.module.api_aurora[0].aws_rds_cluster_instance.serverless_instances[2]' api20251128-production-2
```

Next, update the name of the new table in terraform.tfvars.json for the environment, for example

```json
  "accounts": {
    "production": {
      ...
      "database": {
        "database_name": "api-20251128",
        "aurora_cross_region_backup_enabled": false,
        "aurora_enabled": true,
        "aurora_instance_count": 1,
        "aurora_serverless": true,
        ...
```

From there we can run a plan to check what will happen.

To reduce the diff, provide the container version deployed currently to production. This can most easily be found in the last [Github actions Path to Live run](https://github.com/ministryofjustice/opg-lpa/actions/workflows/workflow_path_to_live.yml).

```sh
aws-vault exec identity -- terraform plan -var container_version=main-v0.324.8
```

We are expecting to see updates to our restored Aurora cluster, and changes to services and resources that reference the table name or ARN.

Things to check for

```text
Policy Documents for API and Admin updating to use new (restored table)

AWS Backup managing the new table

DynamoDB Table tags, point in time restore enabled, server side encryption enabled and TTL activation

ECS Services and Task Definition updates for API and Admin

Plans and Applies always produce a Config file.

Once happy with the plan, apply the changes
```

```sh
aws-vault exec identity -- terraform apply -var container_version=main-v0.324.8
```

## Commit changes

Edit the terraform/environment/.envrc file to set the TF_VAR_default_role back to operator.

<view of the .envrc file, setting the tf_var_default_role value to operator>

update your environment variables from .envrc

```sh
direnv allow
```

or

```sh
source .envrc
```

Commit our changes to the Aurora cluster names, and raise a PR to ensure these persist.

Once this PR is merged and has reached production, we can release the change freeze.

## Deleting this old tables

At this point we can delete the old tables. They are no longer managed by Terraform, so we must do this in the AWS console.

In the AWS console, again while assuming the `breakglass` role in the production account, navigate to the Aurora RDS console.

Select `Databases` from the menu on the left.

Choose the old instances in the old cluster, click `Actions`, then `Delete`.

In the Delete instance screen, type `delete me` to permanently delete the instance and click `Delete`

Choose the old cluster, click `Actions`, then `Delete DB Cluster`

## End of procedure

This is the end of the proceudre
