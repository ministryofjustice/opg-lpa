
resource "aws_iam_role_policy_attachment" "restore_testing_role" {
  role       = aws_iam_role.restore_testing_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForRestores"
}

resource "aws_backup_restore_testing_plan" "restore_testing_plan" {
  name = "${var.environment_name}_restore_testing_plan"

  schedule_expression          = "cron(0 12 ? * * *)" # Daily at 12:00
  schedule_expression_timezone = "UTC"
  start_window_hours           = 4
  recovery_point_selection {
    algorithm             = "LATEST_WITHIN_WINDOW"
    include_vaults        = [aws_backup_vault.main.arn]
    recovery_point_types  = ["CONTINUOUS", "SNAPSHOT"]
    selection_window_days = 7
  }
}

resource "aws_backup_restore_testing_selection" "restore_testing_selection" {
  name                      = "${var.environment_name}_restore_testing_selection"
  restore_testing_plan_name = aws_backup_restore_testing_plan.restore_testing_plan.name
  iam_role_arn              = var.iam_aurora_restore_testing_role_arn

  protected_resource_type = "Aurora"
  protected_resource_arns = [var.source_cluster_arn]
  validation_window_hours = 2
}
