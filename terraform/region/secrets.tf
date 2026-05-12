

# common
resource "aws_secretsmanager_secret" "opg_lpa_common_admin_accounts" {
  name                           = "${local.account_name}/opg_lpa_common_admin_accounts"
  tags                           = local.admin_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

resource "aws_secretsmanager_secret" "opg_lpa_common_account_cleanup_notification_recipients" {
  name                           = "${local.account_name}/opg_lpa_common_account_cleanup_notification_recipients"
  tags                           = local.admin_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

# api secrets
resource "aws_secretsmanager_secret" "opg_lpa_front_csrf_salt" {
  name                           = "${local.account_name}/opg_lpa_front_csrf_salt"
  tags                           = local.api_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

resource "aws_secretsmanager_secret" "opg_lpa_api_notify_api_key" {
  name                           = "${local.account_name}/opg_lpa_api_notify_api_key"
  tags                           = local.api_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

resource "aws_secretsmanager_secret" "opg_lpa_api_auth_log_salt" {
  name                           = "${local.account_name}/opg_lpa_api_auth_log_salt"
  tags                           = local.api_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}


# admin secrets
resource "aws_secretsmanager_secret" "opg_lpa_admin_jwt_secret" {
  name                           = "${local.account_name}/opg_lpa_admin_jwt_secret"
  tags                           = local.admin_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

# front secrets
resource "aws_secretsmanager_secret" "opg_lpa_front_gov_pay_key" {
  name                           = "${local.account_name}/opg_lpa_front_gov_pay_key"
  tags                           = local.front_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

resource "aws_secretsmanager_secret" "opg_lpa_front_os_places_hub_license_key" {
  name                           = "${local.account_name}/opg_lpa_front_os_places_hub_license_key"
  tags                           = local.front_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

# pdf secrets
resource "aws_secretsmanager_secret" "opg_lpa_pdf_owner_password" {
  name                           = "${local.account_name}/opg_lpa_pdf_owner_password"
  tags                           = local.pdf_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

# db secrets
resource "aws_secretsmanager_secret" "api_rds_username" {
  name                           = "${local.account_name}/api_rds_username"
  tags                           = local.db_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

resource "aws_secretsmanager_secret" "api_rds_password" {
  name                           = "${local.account_name}/api_rds_password"
  tags                           = local.db_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

resource "aws_secretsmanager_secret" "api_rds_credentials" {
  name                           = "${local.account_name}/old_api_rds_credentials"
  tags                           = local.db_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

#performance platform db secrets - test
resource "aws_secretsmanager_secret" "performance_platform_db_username" {
  name                           = "${local.account_name}/performance_platform_db_username"
  tags                           = local.performance_platform_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}

resource "aws_secretsmanager_secret" "performance_platform_db_password" {
  name                           = "${local.account_name}/performance_platform_db_password"
  tags                           = local.performance_platform_component_tag
  kms_key_id                     = module.secrets_manager_encryption_key.primary_key.arn
  force_overwrite_replica_secret = true

  replica {
    region     = "eu-west-2"
    kms_key_id = module.secrets_manager_encryption_key.replica_keys.eu-west-2.arn
  }
}
