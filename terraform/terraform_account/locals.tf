# variables for terraform.tfvars.json
variable "account_mapping" {
  type = "map"
}

variable "accounts" {
  type = map(
    object({
      account_id    = string
      is_production = string
    })
  )
}

locals {
  opg_project = "lpa"

  account_name = lookup(var.account_mapping, terraform.workspace, "development")

  account_id = var.accounts[local.account_name].account_id

  # vpc_cidr_ranges = {
  #   development = "10.172.0.0/16"
  #   production  = "10.72.0.0/16"
  # }
  # domains = {
  #   development = "dev.lpa.opg.digital"
  #   production  = "lpa.opg.digital"
  # }

  # vpc_cidr_range       = lookup(local.vpc_cidr_ranges, local.account_name)
  # vpc_name             = lookup(local.vpc_names, local.account_name)
  # domain               = lookup(local.domains, local.account_name)
  # opg_hosted_zone_name = "${lookup(local.domains, local.account_name)}."

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = var.accounts[local.account_name].is_production
  }

  optional_tags = {
    environment-name       = local.account_name
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-webops-runbooks/tree/master/LPA"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
