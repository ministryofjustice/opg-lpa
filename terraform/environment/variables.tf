# variables for terraform.tfvars.json
variable "pagerduty_token" {
}

variable "account_mapping" {
  type = map
}

variable "accounts" {
  type = map(
    object({
      pagerduty_service_name        = string
      account_id                    = string
      is_production                 = string
      front_certificate_domain_name = string
      admin_certificate_domain_name = string
      sirius_api_gateway_endpoint   = string
      sirius_api_gateway_arn        = string
      deletion_protection           = bool
      backup_retention_period       = number
      skip_final_snapshot           = bool
      psql_engine_version           = string
      psql_parameter_group_family   = string
      aurora_enabled                = bool
      aurora_serverless             = bool
      aurora_instance_count         = number
      deletion_protection           = bool
      always_on                     = bool
      db_subnet_group               = string
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
