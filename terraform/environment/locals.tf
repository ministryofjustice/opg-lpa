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
      sirius_api_gateway_endpoint   = string
      sirius_api_gateway_arn        = string
      prevent_db_destroy            = bool
      backup_retention_period       = number
      skip_final_snapshot           = bool
      psql_engine_version           = string
      psql_parameter_group_family   = string
      autoscaling = object({
        front = object({
          minimum = number
          maximum = number
        })
        api = object({
          minimum = number
          maximum = number
        })
        pdf = object({
          minimum = number
          maximum = number
        })
        admin = object({
          minimum = number
          maximum = number
        })
      })
    })
  )
}

# run-time variables
variable "container_version" {
  type    = string
  default = "latest"
}


locals {
  opg_project       = "lpa"
  account_name      = lookup(var.account_mapping, terraform.workspace, "development")
  account           = var.accounts[local.account_name]
  environment       = terraform.workspace
  dns_namespace_env = local.environment == "production" ? "" : "${local.environment}."
  track_from_date   = "2019-04-01"
  front_dns         = "front.lpa"
  admin_dns         = "admin.lpa"

  mandatory_moj_tags = {
    business-unit = "OPG"
    application   = "Online LPA Service"
    owner         = "Amy Wilson: amy.wilson@digital.justice.gov.uk"
    is-production = local.account.is_production
  }

  optional_tags = {
    environment-name       = local.environment
    infrastructure-support = "OPG LPA Product Team: opgteam+online-lpa@digital.justice.gov.uk"
    runbook                = "https://github.com/ministryofjustice/opg-webops-runbooks/tree/master/LPA"
    source-code            = "https://github.com/ministryofjustice/opg-lpa"
  }

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags, {
    "Name" = "${local.environment}-online-lpa-tool"
  }, )

}
