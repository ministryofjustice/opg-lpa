resource "aws_backup_vault_lock_configuration" "cross_account_vault_lock" {
  count              = var.enable_backup_vault_lock ? 1 : 0
  backup_vault_name  = aws_backup_vault.backup_cross_account.name
  max_retention_days = var.backup_vault_lock_max_retention_days
  min_retention_days = var.backup_vault_lock_min_retention_days
}
