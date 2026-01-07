variable "source_cluster_arn" {
  type        = string
  description = "The arn for the source Aurora cluster"
}

variable "retention_period" {
  type        = number
  description = "retention period of DB in days"
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
