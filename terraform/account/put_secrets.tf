resource "aws_kms_key" "secrets_encryption_key" {
  enable_key_rotation = true
}

resource "aws_kms_key" "rds_snapshot_cross_region_encryption_key" {
  enable_key_rotation = true
}

resource "aws_kms_alias" "rds_snapshot_cross_region_encryption" {
  target_key_id = aws_kms_key.rds_snapshot_cross_region_encryption_key.key_id
  name          = "alias/rds-snapshot-cross-region-encryption-key"
}

# common
resource "aws_secretsmanager_secret" "opg_lpa_common_admin_accounts" {
  name       = "${local.account_name}/opg_lpa_common_admin_accounts"
  tags       = merge(local.default_tags, local.admin_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

resource "aws_secretsmanager_secret" "opg_lpa_common_account_cleanup_notification_recipients" {
  name       = "${local.account_name}/opg_lpa_common_account_cleanup_notification_recipients"
  tags       = merge(local.default_tags, local.admin_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

# api secrets
resource "aws_secretsmanager_secret" "opg_lpa_front_csrf_salt" {
  name       = "${local.account_name}/opg_lpa_front_csrf_salt"
  tags       = merge(local.default_tags, local.api_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

resource "aws_secretsmanager_secret" "opg_lpa_api_notify_api_key" {
  name       = "${local.account_name}/opg_lpa_api_notify_api_key"
  tags       = merge(local.default_tags, local.api_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

# admin secrets
resource "aws_secretsmanager_secret" "opg_lpa_admin_jwt_secret" {
  name       = "${local.account_name}/opg_lpa_admin_jwt_secret"
  tags       = merge(local.default_tags, local.admin_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

# front secrets
resource "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_webhook_token" {
  name       = "${local.account_name}/opg_lpa_front_email_sendgrid_webhook_token"
  tags       = merge(local.default_tags, local.front_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

resource "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_api_key" {
  name       = "${local.account_name}/opg_lpa_front_email_sendgrid_api_key"
  tags       = merge(local.default_tags, local.front_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

resource "aws_secretsmanager_secret" "opg_lpa_front_gov_pay_key" {
  name       = "${local.account_name}/opg_lpa_front_gov_pay_key"
  tags       = merge(local.default_tags, local.front_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

resource "aws_secretsmanager_secret" "opg_lpa_front_os_places_hub_license_key" {
  name       = "${local.account_name}/opg_lpa_front_os_places_hub_license_key"
  tags       = merge(local.default_tags, local.front_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

# pdf secrets
resource "aws_secretsmanager_secret" "opg_lpa_pdf_owner_password" {
  name       = "${local.account_name}/opg_lpa_pdf_owner_password"
  tags       = merge(local.default_tags, local.pdf_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

# db secrets
resource "aws_secretsmanager_secret" "api_rds_username" {
  name       = "${local.account_name}/api_rds_username"
  tags       = merge(local.default_tags, local.db_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

resource "aws_secretsmanager_secret" "api_rds_password" {
  name       = "${local.account_name}/api_rds_password"
  tags       = merge(local.default_tags, local.db_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

#performance platform db secrets
resource "aws_secretsmanager_secret" "performance_platform_db_username" {
  name       = "${local.account_name}/performance_platform_db_username"
  tags       = merge(local.default_tags, local.performance_platform_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}

resource "aws_secretsmanager_secret" "performance_platform_db_password" {
  name       = "${local.account_name}/performance_platform_db_password"
  tags       = merge(local.default_tags, local.performance_platform_component_tag)
  kms_key_id = aws_kms_key.secrets_encryption_key.arn
}
