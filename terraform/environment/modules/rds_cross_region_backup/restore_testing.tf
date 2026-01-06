
resource "aws_backup_restore_testing_plan" "aurora_restore_testing_plan" {
  name = "${var.environment_name}_aurora-restore-testing-plan"

  schedule_expression          = "cron(0 12 ? * * *)" # Daily at 12:00
  schedule_expression_timezone = "UTC"
  start_window_hours           = 4

  recovery_point_selection {
    algorithm      = "LATEST_WITHIN_WINDOW"
    include_vaults = [aws_backup_vault.main.name, "production_eu-west-1_aurora_backup_vault"]
    # include_vaults = ["production_eu-west-1_aurora_backup_vault"]
    recovery_point_types  = ["CONTINUOUS"]
    selection_window_days = 7
  }
}


resource "aws_backup_restore_testing_selection" "aurora_restore_selection" {
  name                      = "${var.environment_name}_aurora-restore-selection"
  restore_testing_plan_name = aws_backup_restore_testing_plan.aurora_restore_testing_plan.name
  iam_role_arn              = aws_iam_role.backup_restore_testing_role.arn

  protected_resource_type = "Aurora"
  protected_resource_arns = [aws_rds_cluster.cluster.arn]
  validation_window_hours = 2

}
