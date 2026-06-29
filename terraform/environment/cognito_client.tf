data "aws_cognito_user_pools" "make_a_lasting_power_of_attorney_admin" {
  provider = aws.identity
  name     = "make-a-lasting-power-of-attorney-admin"
}

data "aws_ssm_parameter" "make_a_lasting_power_of_attorney_admin_domain" {
  provider = aws.identity
  name     = "make_a_lasting_power_of_attorney_admin_domain"
}

locals {
  admin_cognito_user_pool_id          = tolist(data.aws_cognito_user_pools.make_a_lasting_power_of_attorney_admin.ids)[0]
  admin_cognito_user_pool_domain_name = "https://${data.aws_ssm_parameter.make_a_lasting_power_of_attorney_admin_domain.value}.auth.eu-west-1.amazoncognito.com"
}

resource "aws_cognito_user_pool_client" "make_a_lasting_power_of_attorney_admin" {
  provider             = aws.identity
  name                 = "${local.environment_name}-admin-auth"
  user_pool_id         = local.admin_cognito_user_pool_id
  allowed_oauth_flows  = ["code"]
  allowed_oauth_scopes = ["openid"]
  # supported_identity_providers = []
  supported_identity_providers         = ["EntraID"]
  allowed_oauth_flows_user_pool_client = true
  explicit_auth_flows = [
    "ALLOW_CUSTOM_AUTH",
    "ALLOW_REFRESH_TOKEN_AUTH",
    "ALLOW_USER_SRP_AUTH",
  ]

  generate_secret = true

  token_validity_units {
    access_token  = "minutes"
    id_token      = "seconds"
    refresh_token = "days"
  }

  access_token_validity  = 5
  id_token_validity      = 3600
  refresh_token_validity = 1
  read_attributes        = []
  write_attributes       = []

  callback_urls = ["https://${module.environment_dns.admin_fqdn}/oauth2/idpresponse"]
  logout_urls   = ["https://${module.environment_dns.admin_fqdn}/"]
}
