# common
data "aws_secretsmanager_secret" "opg_lpa_common_admin_accounts" {
  name = "${local.account_name}/opg_lpa_common_admin_accounts"
}

data "aws_secretsmanager_secret" "opg_lpa_common_account_cleanup_notification_recipients" {
  name = "${local.account_name}/opg_lpa_common_account_cleanup_notification_recipients"
}

# api secrets
data "aws_secretsmanager_secret" "opg_lpa_front_csrf_salt" {
  name = "${local.account_name}/opg_lpa_front_csrf_salt"
}

data "aws_secretsmanager_secret" "opg_lpa_api_notify_api_key" {
  name = "${local.account_name}/opg_lpa_api_notify_api_key"
}

# admin secrets
data "aws_secretsmanager_secret" "opg_lpa_admin_jwt_secret" {
  name = "${local.account_name}/opg_lpa_admin_jwt_secret"
}

# front secrets
data "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_webhook_token" {
  name = "${local.account_name}/opg_lpa_front_email_sendgrid_webhook_token"
}

data "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_api_key" {
  name = "${local.account_name}/opg_lpa_front_email_sendgrid_api_key"
}

data "aws_secretsmanager_secret" "opg_lpa_front_gov_pay_key" {
  name = "${local.account_name}/opg_lpa_front_gov_pay_key"
}

data "aws_secretsmanager_secret" "opg_lpa_front_os_places_hub_license_key" {
  name = "${local.account_name}/opg_lpa_front_os_places_hub_license_key"
}

# pdf secrets
data "aws_secretsmanager_secret" "opg_lpa_pdf_owner_password" {
  name = "${local.account_name}/opg_lpa_pdf_owner_password"
}

# database secrets
data "aws_secretsmanager_secret" "api_rds_username" {
  name = "${local.account_name}/api_rds_username"
}

data "aws_secretsmanager_secret" "api_rds_password" {
  name = "${local.account_name}/api_rds_password"
}

data "aws_secretsmanager_secret_version" "api_rds_username" {
  secret_id = data.aws_secretsmanager_secret.api_rds_username.id
}

data "aws_secretsmanager_secret_version" "api_rds_password" {
  secret_id = data.aws_secretsmanager_secret.api_rds_password.id
}

#performance_platform secrets
data "aws_secretsmanager_secret" "performance_platform_db_username" {
  name = "${local.account_name}/performance_platform_db_username"
}

data "aws_secretsmanager_secret" "performance_platform_db_password" {
  name = "${local.account_name}/performance_platform_db_password"
}

data "aws_secretsmanager_secret_version" "performance_platform_db_username" {
  secret_id = data.aws_secretsmanager_secret.performance_platform_db_username.id
}

data "aws_secretsmanager_secret_version" "performance_platform_db_password" {
  secret_id = data.aws_secretsmanager_secret.performance_platform_db_password.id
}
