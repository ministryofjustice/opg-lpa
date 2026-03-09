variable "environment_name" {
  description = "Environment name used for DMS resource naming."
  type        = string
}

variable "create_iam_roles" {
  description = "Create DMS service-linked IAM roles if they do not exist in the account."
  type        = bool
  default     = false
}

variable "network" {
  description = "Network configuration for DMS resources."
  type = object({
    vpc_id                   = string
    subnet_ids               = list(string)
    source_security_group_id = optional(string)
    target_security_group_id = optional(string)
    allow_all_egress         = optional(bool, true)
  })

  validation {
    condition = (
      var.network.allow_all_egress ||
      try(var.network.source_security_group_id, null) != null ||
      try(var.network.target_security_group_id, null) != null
    )
    error_message = "When network.allow_all_egress is false, set source_security_group_id and/or target_security_group_id to allow required DB egress."
  }
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

  validation {
    condition = (
      !can(trim(var.replication_instance.kms_key_arn)) ||
      trim(var.replication_instance.kms_key_arn) != ""
    )
    error_message = "replication_instance.kms_key_arn must be set to a customer-managed KMS key ARN for DMS encryption."
  }

  validation {
    condition     = try(var.replication_instance.publicly_accessible, false) == false
    error_message = "replication_instance.publicly_accessible must be false in shared VPC environments."
  }
}

variable "task" {
  description = "DMS replication task configuration."
  type = object({
    id                 = string
    migration_type     = optional(string, "full-load-and-cdc")
    table_mappings     = string
    settings           = string
    cdc_start_position = optional(string)
    cdc_start_time     = optional(string)
  })
}

variable "tags" {
  description = "Tags applied to DMS resources (merged with module defaults)."
  type        = map(string)
  default     = {}
}
