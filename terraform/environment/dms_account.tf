
locals {
  # this flag enables DMS resources. Currently prevented from leaving development, and controlled in tfvars.json.
  database_migration_enabled = local.account.database.database_migration_enabled

  dms_network = local.database_migration_enabled ? {
    vpc_id           = module.eu-west-1.vpc_id
    subnet_ids       = module.eu-west-1.app_subnet_ids
    allow_all_egress = false
    security_group_ids = {
      source = module.eu-west-1.db_client_security_group_id,
      target = module.eu-west-1.db_api_security_group_id,
    }
  } : null

  dms_source = local.database_migration_enabled ? {
    cluster_identifier   = "api2-3191lpal1951testwh-cluster"
    username_secret_name = "${local.account_name}/api_rds_username"
    password_secret_name = "${local.account_name}/api_rds_password"
    engine_name          = "aurora-postgresql"
    ssl_mode             = "require"
  } : null

  dms_target = local.database_migration_enabled ? {
    cluster_identifier   = "api2-3196lpal1952create-cluster-clone"
    username_secret_name = "${local.account_name}/api_rds_username"
    password_secret_name = "${local.account_name}/api_rds_password"
    ssl_mode             = "require"
  } : null

  dms_replication_instance = local.database_migration_enabled ? {
    class               = "dms.t3.medium"
    allocated_storage   = 100
    multi_az            = false
    publicly_accessible = false
    apply_immediately   = true
    kms_key_arn         = "alias/opg-lpa-${local.account_name}-rds-encryption-key"
  } : null

  dms_task = local.database_migration_enabled ? {
    id                  = "dms-${local.environment_name}-aurora-migration"
    migration_type      = "full-load"
    table_mappings_file = "table-mappings.json"
  } : null

}
