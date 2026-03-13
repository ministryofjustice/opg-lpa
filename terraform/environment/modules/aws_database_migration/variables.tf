variable "environment_name" {
  description = "Environment name used for DMS resource naming."
  type        = string
}

variable "account_name" {
  description = "Account name used for DMS resource naming and secrets."
  type        = string
}

variable "create_iam_roles" {
  description = "Create DMS service-linked IAM roles if they do not exist in the account."
  type        = bool
  default     = false
}

variable "dms_network" {
  description = "Network configuration for DMS resources."
  type = object({
    vpc_id           = string
    subnet_ids       = list(string)
    allow_all_egress = optional(bool, false)
    security_group_ids = object({
      source = string
      target = string
    })
  })
  default = null
}


variable "source_config" {
  description = "Source database lookup and endpoint settings."
  type = object({
    cluster_identifier          = string
    username_secret_name        = string
    password_secret_name        = string
    engine_name                 = optional(string, "aurora-postgresql")
    ssl_mode                    = optional(string, "none")
    certificate_arn             = optional(string)
    extra_connection_attributes = optional(string)
  })
}

variable "target_config" {
  description = "Target database lookup and endpoint settings."
  type = object({
    cluster_identifier          = string
    username_secret_name        = string
    password_secret_name        = string
    engine_name                 = optional(string, "aurora-postgresql")
    ssl_mode                    = optional(string, "none")
    certificate_arn             = optional(string)
    extra_connection_attributes = optional(string)
  })
}

variable "replication_instance" {
  description = "DMS replication instance configuration."
  type = object({
    class               = optional(string, "dms.t3.medium")
    allocated_storage   = optional(number, 100)
    availability_zone   = optional(string)
    engine_version      = optional(string)
    multi_az            = optional(bool, false)
    publicly_accessible = optional(bool, false)
    apply_immediately   = optional(bool, true)
    kms_key_arn         = optional(string)
  })
  default = {}
}

variable "task" {
  description = "DMS replication task configuration."
  type = object({
    id                 = optional(string)
    migration_type     = optional(string, "full-load-and-cdc")
    table_mappings     = optional(string)
    settings           = optional(string)
    cdc_start_position = optional(string)
    cdc_start_time     = optional(string)
  })
  default = {}
}

variable "tags" {
  description = "Tags applied to DMS resources (merged with module defaults)."
  type        = map(string)
  default     = {}
}
