# variables for terraform.tfvars.json
variable "pagerduty_token" {
  type        = string
  description = "The Pagerduty API token"
}

variable "account_mapping" {
  type        = map(any)
  description = "The mapping of account names to account IDs"
}

variable "default_role" {
  default     = "opg-lpa-ci"
  type        = string
  description = "The default role to use to create resources"
}

variable "management_role" {
  default     = "opg-lpa-ci"
  type        = string
  description = "The default role to use to create resources in the management account"
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
      auth_token_ttl_secs                    = number
      log_retention_in_days                  = number
      account_name_short                     = string
      associate_alb_with_waf_web_acl_enabled = bool
      database = object({
        cluster_identifier                 = string
        aurora_cross_region_backup_enabled = bool
        aurora_restore_testing_enabled     = bool
        daily_backup_deletion              = number
        daily_backup_cold_storage          = number
        monthly_backup_deletion            = number
        monthly_backup_cold_storage        = number
        aurora_instance_count              = number
        aurora_serverless                  = bool
        deletion_protection                = bool
        psql_engine_version                = string
        psql_parameter_group_family        = string
        rds_instance_type                  = string
        rds_proxy_enabled                  = bool
        rds_proxy_routing_enabled          = bool
        skip_final_snapshot                = bool
      })
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
  description = "A map of the account configuration"
}

# run-time variables
variable "container_version" {
  type        = string
  default     = "latest"
  description = "The version of the container to deploy to ECS"
}

output "container_version" {
  value = var.container_version
}
