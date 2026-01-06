
# Iam role policy
resource "aws_iam_role" "backup_restore_testing_role" {
  name = "${var.environment_name}-backup-restore-testing-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Principal = {
          Service = "backup.amazonaws.com"
        }
        Action = "sts:AssumeRole"
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "backup_restore_testing_role" {
  role       = aws_iam_role.backup_restore_testing_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForRestoreTesting"
}

# Restore testing plan
resource "aws_backup_restore_testing_plan" "backup_restore_testing_plan" {
  name = "${var.environment_name}_backup-restore-testing-plan"

  schedule_expression          = "cron(0 12 ? * * *)" # Daily at 12:00
  schedule_expression_timezone = "UTC"
  start_window_hours           = 4
  recovery_point_selection {
    algorithm      = "LATEST_WITHIN_WINDOW"
    include_vaults = [aws_backup_vault.main.arn]
    # include_vaults = ["production_eu-west-1_aurora_backup_vault"]
    recovery_point_types  = ["CONTINUOUS"]
    selection_window_days = 7
  }
}

# Restore testing selection
resource "aws_backup_restore_testing_selection" "backup_restore_testing_selection" {
  name                      = "${var.environment_name}_backup-restore-selection"
  restore_testing_plan_name = "${var.environment_name}_backup-restore-testing-plan"
  iam_role_arn              = aws_iam_role.backup_restore_testing_role.arn

  protected_resource_type = "Aurora"
  protected_resource_arns = [var.source_cluster_arn]
  validation_window_hours = 2

}
