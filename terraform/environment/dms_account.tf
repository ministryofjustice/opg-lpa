
locals {
  # this flag enables DMS resources. Currently prevented from leaving development, and controlled in tfvars.json.
  database_migration_enabled = local.account.database.database_migration_enabled

  dms_config = {
    network = {
      vpc_id           = module.eu-west-1.vpc_id
      subnet_ids       = module.eu-west-1.app_subnet_ids
      allow_all_egress = false
      security_group_ids = {
        source = module.eu-west-1.db_client_security_group_id,
        target = module.eu-west-1.db_api_security_group_id,
      }
    }

    source = {
      cluster_identifier   = "api2-3191lpal1951testwh-cluster"
      username_secret_name = "${local.account_name}/api_rds_username"
      password_secret_name = "${local.account_name}/api_rds_password"
      ssl_mode             = "require"
    }

    target = {
      cluster_identifier   = "api2-3196lpal1952create-cluster-clone"
      username_secret_name = "${local.account_name}/api_rds_username"
      password_secret_name = "${local.account_name}/api_rds_password"
      ssl_mode             = "require"
    }

    replication_instance = {
      kms_key_arn = "alias/opg-lpa-${local.account_name}-rds-encryption-key"
    }

    task = {
      id = "dms-${local.environment_name}-aurora-migration"
    }
  }

}
