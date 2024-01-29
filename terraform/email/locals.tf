# variables for terraform.tfvars.json
variable "accounts" {
  type = map(
    object({
      account_id    = string
      is_production = string
    })
  )
}

locals {

  account_name = "development"
  account      = var.accounts[local.account_name]

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service - Functional Testing Mailbox"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = local.account.is_production
  }

  optional_tags = {
    environment-name       = local.account_name
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-lpa/tree/master/docs/runbooks"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags)
}
