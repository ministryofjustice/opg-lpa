# variables for terraform.tfvars.json
variable "account_mapping" {
  type = map
}

variable "accounts" {
  type = map(
    object({
      account_id                    = string
      is_production                 = string
      front_certificate_domain_name = string
      admin_certificate_domain_name = string
    })
  )
}

locals {
  opg_project = "lpa"

  account_name = lookup(var.account_mapping, terraform.workspace, "development")

  account_id = var.accounts[local.account_name].account_id

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
