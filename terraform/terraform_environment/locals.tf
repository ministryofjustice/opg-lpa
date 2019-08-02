# variables for terraform.tfvars.json
variable "account_mapping" {
  type = "map"
}

variable "accounts" {
  type = map(
    object({
      account_id    = string
      is_production = string
      front_dns     = string
      admin_dns     = string
    })
  )
}

locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account_id   = var.accounts[local.account_name].account_id

  environment       = terraform.workspace
  dns_namespace_env = local.account_name != "development" ? "" : "${terraform.workspace}."


  opg_project = "lpa"
  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = var.accounts[local.account_name].is_production
  }

  optional_tags = {
    environment-name       = terraform.workspace
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-webops-runbooks/tree/master/LPA"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
