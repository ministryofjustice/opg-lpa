#manual only right now - take this out later.
module "eu-west-1" {
  source                    = "./modules/region"
  account                   = local.account
  account_name              = local.account_name
  firewalled_vpc_cidr_range = local.account.firewalled_vpc_cidr_ranges.eu_west_1
  network_cidr_block        = "10.162.0.0/16"
  providers = {
    aws            = aws
    aws.management = aws.management
  }
}

module "eu-west-2" {
  source                    = "./modules/region"
  count                     = local.account.dr_enabled && local.account_name == "development" ? 1 : 0
  account                   = local.account
  account_name              = local.account_name
  firewalled_vpc_cidr_range = local.account.firewalled_vpc_cidr_ranges.eu_west_2
  network_cidr_block        = "10.162.0.0/16"
  providers = {
    aws            = aws.eu-west-2
    aws.management = aws.management_eu_west_2
  }
}
