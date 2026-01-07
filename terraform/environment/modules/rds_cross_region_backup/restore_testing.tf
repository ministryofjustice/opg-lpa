# Iam role policy
resource "aws_iam_role" "restore_testing_role" {
  name = "${var.environment_name}_restore_testing_role"

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

resource "aws_iam_role_policy_attachment" "restore_testing_role" {
  role       = aws_iam_role.restore_testing_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForRestores"
}


# Restore testing plan
resource "aws_backup_restore_testing_plan" "restore_testing_plan" {
  name = "${var.environment_name}_restore_testing_plan"

  schedule_expression          = "cron(0 12 ? * * *)" # Daily at 12:00
  schedule_expression_timezone = "UTC"
  start_window_hours           = 4
  recovery_point_selection {
    algorithm      = "LATEST_WITHIN_WINDOW"
    include_vaults = [aws_backup_vault.main.arn]
    # include_vaults = ["production_eu-west-1_aurora_backup_vault"]
    recovery_point_types  = ["CONTINUOUS", "SNAPSHOT"]
    selection_window_days = 7
  }
}

# Restore testing selection
resource "aws_backup_restore_testing_selection" "restore_testing_selection" {
  name                      = "${var.environment_name}_restore_testing_selection"
  restore_testing_plan_name = aws_backup_restore_testing_plan.restore_testing_plan.name
  iam_role_arn              = aws_iam_role.restore_testing_role.arn

  protected_resource_type = "Aurora"
  protected_resource_arns = [var.source_cluster_arn]
  validation_window_hours = 2

  # restore_metadata_overrides = {
  #   DBClusterIdentifier = "${var.environment_name}-${var.restored_test_cluster}"
  #   DBSubnetGroupName   = aws_db_subnet_group.restored_testing_subnet_group.name
  #   VpcSecurityGroupIds = [aws_security_group.restored_testing_sg.id]
  #   # TODO - NEEDS TO BE DISCUSSED FIRST- restored clusters shouldnt be created in the same VPC as the source cluster (according to docs and best practices)
  # }



}
