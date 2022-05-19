module "eu-west-1" {
  source                   = "./modules/environment"
  account                  = local.account
  account_name             = local.account_name
  environment_name         = local.environment_name
  region_name              = "eu-west-1"
  container_version        = var.container_version
  lambda_container_version = var.lambda_container_version
  pagerduty_token          = var.pagerduty_token
  management_role          = var.management_role
  default_role             = var.default_role

  providers = {
    aws            = aws.eu_west_1
    aws.management = aws.management
  }
}

module "eu-west-2" {
  count                    = local.dr_enabled ? 1 : 0
  source                   = "./modules/environment"
  account                  = local.account
  account_name             = local.account_name
  environment_name         = local.environment_name
  region_name              = "eu-west-2"
  container_version        = var.container_version
  lambda_container_version = var.lambda_container_version
  pagerduty_token          = var.pagerduty_token
  management_role          = var.management_role
  default_role             = var.default_role

  providers = {
    aws            = aws.eu_west_2
    aws.management = aws.management
  }
}

module "environment_dns" {
  source = "./modules/dns"
  providers = {
    aws            = aws
    aws.management = aws.management
  }
  account_name     = local.account_name
  environment_name = local.environment_name
  front_dns_name   = !local.dr_enabled ? module.eu-west-1.front_dns_name : module.eu-west-2[0].front_dns_name
  front_zone_id    = !local.dr_enabled ? module.eu-west-1.front_zone_id : module.eu-west-2[0].front_zone_id
  admin_dns_name   = !local.dr_enabled ? module.eu-west-1.admin_dns_name : module.eu-west-2[0].admin_dns_name
  admin_zone_id    = !local.dr_enabled ? module.eu-west-1.admin_zone_id : module.eu-west-2[0].admin_zone_id

}

output "admin-domain" {
  value = module.environment_dns.admin_domain
}

output "front-domain" {
  value = module.environment_dns.front_domain
}
