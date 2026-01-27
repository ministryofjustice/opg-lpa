
variable "account" {
  type = object({
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
    always_on                              = bool
    log_retention_in_days                  = number
    account_name_short                     = string
    associate_alb_with_waf_web_acl_enabled = bool
    firewalled_networks_enabled            = bool
    database = object({
      cluster_identifier                 = string
      aurora_cross_region_backup_enabled = bool
      aurora_restore_testing_enabled     = bool
      daily_backup_deletion              = number
      daily_backup_cold_storage          = number
      monthly_backup_deletion            = number
      monthly_backup_cold_storage        = number
      aurora_enabled                     = bool
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
}

variable "account_name" {
  type = string
}

variable "environment_name" {
  type = string
}

variable "region_name" {
  type = string
}

variable "ecs_iam_task_roles" {
  type = object({
    front = object({
      name = string
      arn  = string
      id   = string
    })
    api = object({
      name = string
      arn  = string
      id   = string
    })
    pdf = object({
      name = string
      arn  = string
      id   = string
    })
    admin = object({
      name = string
      arn  = string
      id   = string
    })
    seeding = object({
      name = string
      arn  = string
      id   = string
    })
    cloudwatch_events = object({
      name = string
      arn  = string
      id   = string
    })
  })
  description = "IAM roles to be used by the ECS tasks"
}

variable "ecs_execution_role" {
  type = object({
    name = string
    arn  = string
  })
  description = "The ARN of the ECS execution role"
}

# run-time variables
variable "container_version" {
  type    = string
  default = "latest"
}
