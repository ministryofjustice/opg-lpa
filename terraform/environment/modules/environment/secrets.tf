# common
data "aws_secretsmanager_secret" "opg_lpa_common_admin_accounts" {
  name = "${var.account_name}/opg_lpa_common_admin_accounts"
}

data "aws_secretsmanager_secret" "opg_lpa_common_account_cleanup_notification_recipients" {
  name = "${var.account_name}/opg_lpa_common_account_cleanup_notification_recipients"
}

# api secrets
data "aws_secretsmanager_secret" "opg_lpa_front_csrf_salt" {
  name = "${var.account_name}/opg_lpa_front_csrf_salt"
}

data "aws_secretsmanager_secret" "opg_lpa_api_notify_api_key" {
  name = "${var.account_name}/opg_lpa_api_notify_api_key"
}

# admin secrets
data "aws_secretsmanager_secret" "opg_lpa_admin_jwt_secret" {
  name = "${var.account_name}/opg_lpa_admin_jwt_secret"
}

# front secrets
data "aws_secretsmanager_secret" "opg_lpa_front_gov_pay_key" {
  name = "${var.account_name}/opg_lpa_front_gov_pay_key"
}

data "aws_secretsmanager_secret" "opg_lpa_front_os_places_hub_license_key" {
  name = "${var.account_name}/opg_lpa_front_os_places_hub_license_key"
}

# pdf secrets
data "aws_secretsmanager_secret" "opg_lpa_pdf_owner_password" {
  name = "${var.account_name}/opg_lpa_pdf_owner_password"
}

# database secrets
data "aws_secretsmanager_secret" "api_rds_username" {
  name = "${var.account_name}/api_rds_username"
}

data "aws_secretsmanager_secret" "api_rds_password" {
  name = "${var.account_name}/api_rds_password"
}

data "aws_secretsmanager_secret_version" "api_rds_username" {
  secret_id = data.aws_secretsmanager_secret.api_rds_username.id
}

data "aws_secretsmanager_secret_version" "api_rds_password" {
  secret_id = data.aws_secretsmanager_secret.api_rds_password.id
}

#performance_platform secrets
data "aws_secretsmanager_secret" "performance_platform_db_username" {
  name = "${var.account_name}/performance_platform_db_username"
}

data "aws_secretsmanager_secret" "performance_platform_db_password" {
  name = "${var.account_name}/performance_platform_db_password"
}

resource "aws_secretsmanager_secret" "api_rds_credentials" {
  count                   = 1
  name                    = "${var.environment_name}/api_rds_credentials"
  recovery_window_in_days = 0
}

resource "aws_secretsmanager_secret_version" "api_rds_credentials" {
  count     = 1
  secret_id = aws_secretsmanager_secret.api_rds_credentials[0].id
  secret_string = jsonencode({
    username            = data.aws_secretsmanager_secret_version.api_rds_username.secret_string,
    password            = data.aws_secretsmanager_secret_version.api_rds_password.secret_string,
    engine              = "postgres",
    host                = module.api_aurora[0].endpoint,
    port                = "5432",
    dbClusterIdentifier = "api2-${var.environment_name}"
  })
}
