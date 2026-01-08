resource "aws_iam_role" "restore_testing_role" {
  name = "${local.environment_name}_restore_testing_role"

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

moved {
  from = module.cross_region_backup.restore_testing.restore_testing_iam_role
  to   = aws_iam_role.iam_aurora_restore_testing_role
}
