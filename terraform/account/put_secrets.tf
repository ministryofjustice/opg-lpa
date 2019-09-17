# common
resource "aws_secretsmanager_secret" "opg_lpa_common_admin_accounts" {
  name = "${local.account_name}/opg_lpa_common_admin_accounts"
}

resource "aws_secretsmanager_secret" "opg_lpa_common_account_cleanup_notification_recipients" {
  name = "${local.account_name}/opg_lpa_common_account_cleanup_notification_recipients"
}

# api secrets
resource "aws_secretsmanager_secret" "opg_lpa_front_csrf_salt" {
  name = "${local.account_name}/opg_lpa_front_csrf_salt"
}

resource "aws_secretsmanager_secret" "opg_lpa_api_notify_api_key" {
  name = "${local.account_name}/opg_lpa_api_notify_api_key"
}

# admin secrets
resource "aws_secretsmanager_secret" "opg_lpa_admin_jwt_secret" {
  name = "${local.account_name}/opg_lpa_admin_jwt_secret"
}

# front secrets
resource "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_webhook_token" {
  name = "${local.account_name}/opg_lpa_front_email_sendgrid_webhook_token"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_api_key" {
  name = "${local.account_name}/opg_lpa_front_email_sendgrid_api_key"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_gov_pay_key" {
  name = "${local.account_name}/opg_lpa_front_gov_pay_key"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_ordnance_survey_license_key" {
  name = "${local.account_name}/opg_lpa_front_ordnance_survey_license_key"
}

# pdf secrets
resource "aws_secretsmanager_secret" "opg_lpa_pdf_owner_password" {
  name = "${local.account_name}/opg_lpa_pdf_owner_password"
}

resource "aws_secretsmanager_secret" "api_rds_username" {
  name = "${local.account_name}/api_rds_username"
}

resource "aws_secretsmanager_secret" "api_rds_password" {
  name = "${local.account_name}/api_rds_password"
}
