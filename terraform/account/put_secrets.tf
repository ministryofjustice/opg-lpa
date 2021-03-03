# common
resource "aws_secretsmanager_secret" "opg_lpa_common_admin_accounts" {
  name = "${local.account_name}/opg_lpa_common_admin_accounts"
  tags = local.default_tags
}

resource "aws_secretsmanager_secret" "opg_lpa_common_account_cleanup_notification_recipients" {
  name = "${local.account_name}/opg_lpa_common_account_cleanup_notification_recipients"
  tags = local.default_tags
}

# api secrets
resource "aws_secretsmanager_secret" "opg_lpa_front_csrf_salt" {
  name = "${local.account_name}/opg_lpa_front_csrf_salt"
  tags = local.default_tags
}

resource "aws_secretsmanager_secret" "opg_lpa_api_notify_api_key" {
  name = "${local.account_name}/opg_lpa_api_notify_api_key"
  tags = local.default_tags
}

# admin secrets
resource "aws_secretsmanager_secret" "opg_lpa_admin_jwt_secret" {
  name = "${local.account_name}/opg_lpa_admin_jwt_secret"
  tags = local.default_tags
}

# front secrets
resource "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_webhook_token" {
  name = "${local.account_name}/opg_lpa_front_email_sendgrid_webhook_token"
  tags = local.default_tags
}

resource "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_api_key" {
  name = "${local.account_name}/opg_lpa_front_email_sendgrid_api_key"
  tags = local.default_tags
}

resource "aws_secretsmanager_secret" "opg_lpa_front_gov_pay_key" {
  name = "${local.account_name}/opg_lpa_front_gov_pay_key"
  tags = local.default_tags
}

resource "aws_secretsmanager_secret" "opg_lpa_front_os_places_hub_license_key" {
  name = "${local.account_name}/opg_lpa_front_os_places_hub_license_key"
  tags = local.default_tags
}

# pdf secrets
resource "aws_secretsmanager_secret" "opg_lpa_pdf_owner_password" {
  name = "${local.account_name}/opg_lpa_pdf_owner_password"
  tags = local.default_tags
}

resource "aws_secretsmanager_secret" "api_rds_username" {
  name = "${local.account_name}/api_rds_username"
  tags = local.default_tags
}

resource "aws_secretsmanager_secret" "api_rds_password" {
  name = "${local.account_name}/api_rds_password"
  tags = local.default_tags
}

resource "aws_secretsmanager_secret" "slack_incoming_webhook" {
  name = "${local.account_name}/slack_incoming_webhook"
  tags = local.default_tags
}
