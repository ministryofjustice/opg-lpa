# common
resource "aws_secretsmanager_secret" "opg_base_ssl_cert" {
  name = "${local.account_name}/opg_base_ssl_cert"
}

resource "aws_secretsmanager_secret" "opg_base_ca_cert" {
  name = "${local.account_name}/opg_base_ca_cert"
}

resource "aws_secretsmanager_secret" "opg_base_ssl_key" {
  name = "${local.account_name}/opg_base_ssl_key"
}

resource "aws_secretsmanager_secret" "opg_lpa_common_sentry_api_uri" {
  name = "${local.account_name}/opg_lpa_common_sentry_api_uri"
}

resource "aws_secretsmanager_secret" "opg_lpa_common_admin_accounts" {
  name = "${local.account_name}/opg_lpa_common_admin_accounts"
}

resource "aws_secretsmanager_secret" "opg_lpa_common_account_cleanup_notification_recipients" {
  name = "${local.account_name}/opg_lpa_common_account_cleanup_notification_recipients"
}

# api secrets
resource "aws_secretsmanager_secret" "opg_lpa_api_pdf_encryption_key_queue" {
  name = "${local.account_name}/opg_lpa_api_pdf_encryption_key_queue"
}

resource "aws_secretsmanager_secret" "opg_lpa_api_pdf_encryption_key_document" {
  name = "${local.account_name}/opg_lpa_api_pdf_encryption_key_document"
}

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
resource "aws_secretsmanager_secret" "opg_lpa_front_session_encryption_key" {
  name = "${local.account_name}/opg_lpa_front_session_encryption_key"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_session_encryption_keys" {
  name = "${local.account_name}/opg_lpa_front_session_encryption_keys"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_webhook_token" {
  name = "${local.account_name}/opg_lpa_front_email_sendgrid_webhook_token"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_email_sendgrid_api_key" {
  name = "${local.account_name}/opg_lpa_front_email_sendgrid_api_key"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_postcode_license_key" {
  name = "${local.account_name}/opg_lpa_front_postcode_license_key"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_postcode_info_token" {
  name = "${local.account_name}/opg_lpa_front_postcode_info_token"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_nginx_auth" {
  name = "${local.account_name}/opg_lpa_front_nginx_auth"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_gov_pay_key" {
  name = "${local.account_name}/opg_lpa_front_gov_pay_key"
}

resource "aws_secretsmanager_secret" "opg_lpa_front_ordnance_survey_license_key" {
  name = "${local.account_name}/opg_lpa_front_ordnance_survey_license_key"
}

# pdf secrets
resource "aws_secretsmanager_secret" "opg_lpa_pdf_encryption_key_queue" {
  name = "${local.account_name}/opg_lpa_pdf_encryption_key_queue"
}

resource "aws_secretsmanager_secret" "opg_lpa_pdf_encryption_key_document" {
  name = "${local.account_name}/opg_lpa_pdf_encryption_key_document"
}

resource "aws_secretsmanager_secret" "opg_lpa_pdf_owner_password" {
  name = "${local.account_name}/opg_lpa_pdf_owner_password"
}

resource "aws_secretsmanager_secret" "api_rds_username" {
  name = "${local.account_name}/api_rds_username"
}

resource "aws_secretsmanager_secret" "api_rds_password" {
  name = "${local.account_name}/api_rds_password"
}
