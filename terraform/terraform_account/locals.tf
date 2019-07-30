# variables for terraform.tfvars.json
variable "account_mapping" {
  type = "map"
}
variable "account_ids" {
  type = "map"
}
variable "is_production" {
  type = "map"
}

locals {
  opg_project = "lpa"

  account_name = lookup(var.account_mapping, terraform.workspace, "development")

  account_id = lookup(var.account_ids, local.account_name)

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

  opg_account_id = local.account_id

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = lookup(var.is_production, local.account_name)
  }

  optional_tags = {
    environment-name       = terraform.workspace
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-webops-runbooks/tree/master/LPA"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
