data "aws_cloudwatch_log_group" "cloudtrail" {
  name = "online_lpa_cloudtrail_${local.account_name}"
}