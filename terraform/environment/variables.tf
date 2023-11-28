# variables for terraform.tfvars.json
variable "pagerduty_token" {
}

variable "account_mapping" {
  type = map(any)
}

variable "default_role" {
  default = "opg-lpa-ci"
}

variable "management_role" {
  default = "opg-lpa-ci"
}

variable "lambda_container_version" {
  type    = string
  default = "latest"
}

variable "accounts" {
  type = map(
    object({
      dr_enabled                             = bool
      performance_platform_enabled           = bool
      pagerduty_service_name                 = string
      account_id                             = string
      is_production                          = string
      sirius_api_gateway_endpoint            = string
      sirius_api_gateway_arn                 = string
      sirius_api_healthcheck_arn             = string
      telemetry_requests_sampled_fraction    = string
      backup_retention_period                = number
      auth_token_ttl_secs                    = number
      skip_final_snapshot                    = bool
      psql_engine_version                    = string
      psql13_parameter_group_family          = string
      aurora_enabled                         = bool
      aurora_serverless                      = bool
      aurora_instance_count                  = number
      aurora_cross_region_backup_enabled     = bool
      deletion_protection                    = bool
      always_on                              = bool
      log_retention_in_days                  = number
      account_name_short                     = string
      associate_alb_with_waf_web_acl_enabled = bool
      rds_instance_type                      = string
      regions = map(
        object({
          region     = string
          is_primary = string
      }))
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
