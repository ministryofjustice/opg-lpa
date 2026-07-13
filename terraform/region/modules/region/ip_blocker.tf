module "ip_blocker" {
  source                       = "./modules/ip_blocker"
  dynamodb_kms_key_arn         = var.dynamodb_kms_key_arn
  application_logs_kms_key_arn = var.application_logs_kms_key_arn
  lambda_function_aws_iam_role = var.aws_iam_roles.ip_blocker
  waf_ip_blocking_enabled      = var.web_application_firewall.waf_ip_blocking_enabled
  monitored_log_group_name     = "${var.account_name}_application_logs"
  monitored_log_stream_prefix  = "${var.account_name}.front-web.online-lpa"
}
