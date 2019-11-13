# variables for terraform.tfvars.json
variable "account_mapping" {
  type = "map"
}

variable "accounts" {
  type = map(
    object({
      account_id                    = string
      is_production                 = string
      front_dns                     = string
      admin_dns                     = string
      front_certificate_domain_name = string
      admin_certificate_domain_name = string
      sirius_api_gateway_endpoint   = string
      sirius_api_gateway_arn        = string
      prevent_db_destroy            = string
    })
  )
}

# run-time variables
variable "container_version" {
  type    = string
  default = "latest"
}


locals {
  account_name = lookup(var.account_mapping, terraform.workspace, "development")
  account_id   = var.accounts[local.account_name].account_id

  environment       = terraform.workspace
  dns_namespace_env = local.account_name != "development" ? "" : "${local.environment}."

  backup_retention_period = local.account_name != "development" ? 14 : 0

  sirius_api_gateway_endpoint = var.accounts[local.account_name].sirius_api_gateway_endpoint
  sirius_api_gateway_arn      = var.accounts[local.account_name].sirius_api_gateway_arn
  prevent_db_destroy          = var.accounts[local.account_name].prevent_db_destroy

  // Set minimum count for each service.
  ecs_minimum_task_count_front = local.account_name == "development" ? 1 : 3
  ecs_minimum_task_count_api   = local.account_name == "development" ? 1 : 3
  ecs_minimum_task_count_pdf   = local.account_name == "development" ? 1 : 2
  ecs_minimum_task_count_admin = local.account_name == "development" ? 1 : 2

  track_from_date = "2019-04-01"

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

  default_tags = merge(local.mandatory_moj_tags, local.optional_tags, {
    "Name" = "${local.environment}-online-lpa-tool"
  }, )

}
