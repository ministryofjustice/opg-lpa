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
    aws             = aws.eu_west_1
    aws.destination = aws.eu_west_2
  }

  source_cluster_arn                  = module.eu-west-1.aws_aurora_cluster_arn
  environment_name                    = local.environment_name
  destination_region_name             = "eu-west-2"
  key_alias                           = "mrk_db_snapshot_key-${local.account_name}"
  account_name                        = local.account_name
  iam_aurora_restore_testing_role_arn = aws_iam_role.restore_testing_role.arn
  aurora_restore_testing_enabled      = local.account.database.aurora_restore_testing_enabled
  daily_backup_deletion               = local.account.database.daily_backup_deletion
  daily_backup_cold_storage           = local.account.database.daily_backup_cold_storage
  monthly_backup_deletion             = local.account.database.monthly_backup_deletion
  monthly_backup_cold_storage         = local.account.database.monthly_backup_cold_storage

}

output "admin_domain" {
  value = module.environment_dns.admin_domain
}

output "front_domain" {
  value = module.environment_dns.front_domain
}

output "front_fqdn" {
  value = module.environment_dns.front_fqdn
}

output "admin_fqdn" {
  value = module.environment_dns.admin_fqdn
}

output "front_sg_id" {
  value = !local.dr_enabled ? module.eu-west-1.front_sg_id : module.eu-west-2[0].front_sg_id
}

output "admin_sg_id" {
  value = !local.dr_enabled ? module.eu-west-1.admin_sg_id : module.eu-west-2[0].admin_sg_id
}
