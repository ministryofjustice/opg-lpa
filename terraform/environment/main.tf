module "eu-west-1" {
  source                   = "./modules/environment"
  account                  = local.account
  account_name             = local.account_name
  environment_name         = local.environment
  region_name              = "eu-west-1"
  container_version        = var.container_version
  lambda_container_version = var.lambda_container_version
  pagerduty_token          = var.pagerduty_token
  management_role          = var.management_role
  default_role             = var.default_role


  providers = {
    aws            = aws
    aws.management = aws.management
  }
}

output "admin-domain" {
  value = module.eu-west-1.admin-domain
}

output "front-domain" {
  value = module.eu-west-1.front-domain
}
