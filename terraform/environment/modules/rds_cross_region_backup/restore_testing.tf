
# Iam role policy
resource "aws_iam_role" "backup_restore_testing_role" {
  name = "${var.environment_name}_backup_restore_testing_role"

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
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForRestores"
}


# Restore testing plan
resource "aws_backup_restore_testing_plan" "backup_restore_testing_plan" {
  name = aws_backup_restore_testing.plan.backup_restore_testing_plan.name

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
  name                      = "${var.environment_name}_backup_restore_selection"
  restore_testing_plan_name = "${var.environment_name}_backup_restore_testing_plan"
  iam_role_arn              = aws_iam_role.backup_restore_testing_role.arn
  restore_metadata_overrides = {
    DBClusterIdentifier = "${var.environment_name}_restored_testing_cluster"
    DBSubnetGroupName   = "${var.environment_name}_restored_testing_subnet_group"
    VpcSecurityGroupIds = [aws_security_group.restored_testing_sg.id]
    # TODO - NEEDS TO BE discussed- restored clusters cannot be created in the same VPC as the source cluster
  }

  protected_resource_type = "Aurora"
  protected_resource_arns = [var.source_cluster_arn]
  validation_window_hours = 2

}
