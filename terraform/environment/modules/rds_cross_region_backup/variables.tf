variable "source_cluster_arn" {
  type        = string
  description = "The arn for the source Aurora cluster"
}

variable "environment_name" {
  type        = string
  description = "environment name"
}

variable "account_name" {
  type        = string
  description = "account name"
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

variable "cross_account_backup_enabled" {
  type        = bool
  description = "Condition to enable cross account backup"
}

variable "enable_backup_vault_lock" {
  type        = bool
  description = "Enable backup vault lock for the cross account backup vault"
}

variable "backup_vault_lock_min_retention_days" {
  type        = number
  description = "Minimum retention days for backup vault lock"
}

variable "backup_vault_lock_max_retention_days" {
  type        = number
  description = "Maximum retention days for backup vault lock (0 means no maximum)"
}

variable "key_alias" {
  type        = string
  description = "The alias for the KMS key used to encrypt RDS snapshots and backups"
}

variable "account_id" {
  type        = string
  description = "AWS account ID of the primary account where the Aurora cluster is located. Required if cross_account_backup_enabled is true."
}

# to be deleted once backup module ahs been migrated to preprod and prod and new vaults/keys being used
variable "destination_region_name" {
  type        = string
  description = "destination key name"
}
