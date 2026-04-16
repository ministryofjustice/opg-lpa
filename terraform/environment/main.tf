module "eu-west-1" {
  source             = "./modules/environment"
  account            = local.account
  account_name       = local.account_name
  environment_name   = local.environment_name
  region_name        = "eu-west-1"
  container_version  = var.container_version
  ecs_execution_role = aws_iam_role.execution_role
  ecs_iam_task_roles = {
    api               = aws_iam_role.api_task_role
    front             = aws_iam_role.front_task_role
    admin             = aws_iam_role.admin_task_role
    pdf               = aws_iam_role.pdf_task_role
    seeding           = aws_iam_role.seeding_task_role
    cloudwatch_events = aws_iam_role.cloudwatch_events_ecs_role
  }
  providers = {
    aws            = aws.eu_west_1
    aws.management = aws.management
  }
}

module "eu-west-2" {
  count              = local.dr_enabled ? 1 : 0
  source             = "./modules/environment"
  account            = local.account
  account_name       = local.account_name
  environment_name   = local.environment_name
  region_name        = "eu-west-2"
  container_version  = var.container_version
  ecs_execution_role = aws_iam_role.execution_role
  ecs_iam_task_roles = {
    api               = aws_iam_role.api_task_role
    front             = aws_iam_role.front_task_role
    admin             = aws_iam_role.admin_task_role
    pdf               = aws_iam_role.pdf_task_role
    seeding           = aws_iam_role.seeding_task_role
    cloudwatch_events = aws_iam_role.cloudwatch_events_ecs_role
  }

  providers = {
    aws            = aws.eu_west_2
    aws.management = aws.management
  }
}

module "environment_dns" {
  source = "./modules/dns"
  providers = {
    aws            = aws
    aws.us_east_1  = aws.us_east_1
    aws.management = aws.management
  }
  account_name     = local.account_name
  environment_name = local.environment_name
  front_dns_name   = !local.dr_enabled ? module.eu-west-1.front_dns_name : module.eu-west-2[0].front_dns_name
  front_zone_id    = !local.dr_enabled ? module.eu-west-1.front_zone_id : module.eu-west-2[0].front_zone_id
  admin_dns_name   = !local.dr_enabled ? module.eu-west-1.admin_dns_name : module.eu-west-2[0].admin_dns_name
  admin_zone_id    = !local.dr_enabled ? module.eu-west-1.admin_zone_id : module.eu-west-2[0].admin_zone_id

}

module "cross_region_backup" {
  count  = local.account.database.aurora_cross_region_backup_enabled ? 1 : 0
  source = "./modules/rds_cross_region_backup"
  providers = {
    aws             = aws
    aws.replica     = aws.replica
    aws.backup      = aws.backup
    aws.destination = aws.destination
  }

  source_cluster_arn                   = module.eu-west-1.aws_aurora_cluster_arn
  account_id                           = local.account.account_id
  environment_name                     = local.environment_name
  destination_region_name              = "eu-west-2"
  key_alias                            = "mrk_db_snapshot_key-${local.account_name}"
  account_name                         = local.account_name
  aurora_restore_testing_enabled       = local.account.database.aurora_restore_testing_enabled
  cross_account_backup_enabled         = local.account.database.cross_account_backup_enabled
  daily_backup_deletion                = local.account.database.daily_backup_deletion
  daily_backup_cold_storage            = local.account.database.daily_backup_cold_storage
  monthly_backup_deletion              = local.account.database.monthly_backup_deletion
  monthly_backup_cold_storage          = local.account.database.monthly_backup_cold_storage
  enable_backup_vault_lock             = local.account.database.enable_backup_vault_lock
  backup_vault_lock_min_retention_days = local.account.database.daily_backup_deletion
  backup_vault_lock_max_retention_days = local.account.database.monthly_backup_deletion
}

module "aws_database_migration" {
  count  = local.database_migration_enabled ? 1 : 0
  source = "./modules/aws_database_migration"

  providers = {
    aws           = aws.eu_west_1
    aws.eu_west_1 = aws.eu_west_1
  }
  account_name         = local.account_name
  environment_name     = local.environment_name
  create_iam_roles     = true
  dms_network          = local.dms_network
  source_config        = local.dms_source
  target_config        = local.dms_target
  replication_instance = local.dms_replication_instance
  task                 = local.dms_task
}
