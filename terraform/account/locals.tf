# variables for terraform.tfvars.json
variable "account_mapping" {
  type = map(any)
}

variable "accounts" {
  type = map(
    object({
      account_id        = string
      is_production     = string
      retention_in_days = number
    })
  )
}

variable "pagerduty_token" {
}

locals {
  opg_project                 = "lpa"
  pager_duty_ops_service_name = "Make a Lasting Power of Attorney Ops Monitoring"
  account_name                = lookup(var.account_mapping, terraform.workspace, "development")
  account                     = var.accounts[local.account_name]
  account_id                  = local.account.account_id
  cert_prefix_internal        = local.account_name == "production" ? "" : "*."
  cert_prefix_public_facing   = local.account_name == "production" ? "www." : "*."
  cert_prefix_development     = local.account_name == "development" ? "development." : ""

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
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

  shared_component_tag = {
    component = "shared"
  }

  admin_component_tag = {
    component = "admin"
  }

  front_component_tag = {
    component = "front"
  }

  api_component_tag = {
    component = "api"
  }

  pdf_component_tag = {
    component = "pdf"
  }

  db_component_tag = {
    component = "db"
  }

  performance_platform_component_tag = {
    component = "performance_platform"
  }

}
