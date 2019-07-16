# variables for terraform.tfvars.json
variable "account_mapping" {
  type = "map"
}

locals {
  opg_project = "lpa"

  account_name = lookup(var.account_mapping, terraform.workspace, "development")

  account_id = lookup(local.account_ids, local.account_name)

  account_ids = {
    development = "001780581745"
    production  = "550790013665"
  }
  vpc_cidr_ranges = {
    development = "10.172.0.0/16"
    production  = "10.72.0.0/16"
  }
  vpc_names = {
    development = "dev-vpc"
    production  = "prod-vpc"
  }
  domains = {
    development = "dev.lpa.opg.digital"
    production  = "lpa.opg.digital"
  }
  is_production = {
    development = false
    production  = true
  }

  vpc_cidr_range       = lookup(local.vpc_cidr_ranges, local.account_name)
  vpc_name             = lookup(local.vpc_names, local.account_name)
  domain               = lookup(local.domains, local.account_name)
  opg_hosted_zone_name = "${lookup(local.domains, local.account_name)}."

  opg_account_id  = local.account_id
  opg_environment = local.account_name

  tags = {
    Environment            = local.opg_environment
    Project                = local.opg_project
    Stack                  = local.vpc_name
    application            = local.opg_project
    business-unit          = "OPG"
    environment-name       = local.opg_environment
    infrastructure-support = "OPGOPS opgteam@digital.justice.gov.uk"
    is-production          = lookup(local.is_production, local.account_name)
    owner                  = "OPGOPS opgteam@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-webops-runbooks/tree/master/LPA"
    source-code            = "https://gitlab.service.opg.digital/opsforks/opg-lpa-deploy"
  }
}
