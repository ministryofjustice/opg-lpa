
resource "aws_ssm_service_setting" "public_sharing_permission" {
  setting_id    = "arn:aws:ssm:${data.aws_region.current.region}:${local.account.account_id}:servicesetting/ssm/documents/console/public-sharing-permission"
  setting_value = "Disable"
}
