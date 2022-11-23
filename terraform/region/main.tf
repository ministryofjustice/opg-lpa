#manual only right now - take this out later.
module "eu-west-1" {
  source       = "./modules/region"
  account      = local.account
  account_name = local.account_name
  providers = {
    aws            = aws
    aws.management = aws.management
  }
}

module "eu-west-2" {
  source       = "./modules/region"
  count        = local.account.dr_enabled && local.account_name == "development" ? 1 : 0
  account      = local.account
  account_name = local.account_name
  providers = {
    aws            = aws.eu-west-2
    aws.management = aws.management_eu_west_2
  }
}
