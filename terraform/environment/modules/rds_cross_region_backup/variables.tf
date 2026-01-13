variable "source_cluster_arn" {
  type        = string
  description = "The arn for the source Aurora cluster"
}

variable "destination_region_name" {
  type        = string
  description = "destination key name"
}

variable "key_alias" {
  type        = string
  description = "key alias"
}

variable "environment_name" {
  type        = string
  description = "environment name"
}

variable "account_name" {
  type        = string
  description = "account name"
}
variable "iam_aurora_restore_testing_role_arn" {
  type        = string
  description = "The ARN of the IAM role for Aurora restore testing"
}

variable "aurora_restore_testing_enabled" {
  type        = string
  description = "Condition to switch on the aurora restore testing role"
}

variable "daily_backup_deletion" {
  type        = number
  description = "Number of days to retain daily backups before deletion"
}

variable "monthly_backup_deletion" {
  type        = number
  description = "Number of days to retain monthly backups before deletion"
}
variable "daily_backup_cold_storage" {
  type        = number
  description = "Number of days to retain daily backups in cold storage"
}
variable "monthly_backup_cold_storage" {
  type        = number
  description = "Number of days to retain monthly backups in cold storage"

}
