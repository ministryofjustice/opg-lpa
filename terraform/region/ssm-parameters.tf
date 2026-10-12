resource "aws_ssm_service_setting" "public_sharing_permission_eu_west_1" {
  setting_id    = "arn:aws:ssm:eu-west-1:${local.account.account_id}:servicesetting/ssm/documents/console/public-sharing-permission"
  setting_value = "Disable"
  provider      = aws.eu-west-1
}

resource "aws_ssm_service_setting" "public_sharing_permission_eu_west_2" {
  setting_id    = "arn:aws:ssm:eu-west-2:${local.account.account_id}:servicesetting/ssm/documents/console/public-sharing-permission"
  setting_value = "Disable"
  provider      = aws.eu-west-2
}
