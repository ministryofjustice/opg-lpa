data "aws_iam_role" "aurora_backup_role" {
  name = "aurora_cluster_backup_role"
}


data "aws_iam_policy_document" "aurora_backup_role" {
  statement {
    actions = ["kms:Encrypt", "kms:CreateGrant", "kms:Decrypt", "kms:ReEncrypt*", "kms:GenerateDataKey*", "kms:DescribeKey"]

    resources = [
      data.aws_kms_key.source_rds_snapshot_key.arn,
      data.aws_kms_key.destination_rds_snapshot_key.arn,
      data.aws_kms_key.backup.arn
    ]
  }
}


resource "aws_iam_policy" "aurora_backup_resources" {
  name        = "${var.environment_name}_aurora_backup_role_policy"
  description = "Policies for aurora backup role"
  policy      = data.aws_iam_policy_document.aurora_backup_role.json
}

resource "aws_iam_role_policy_attachment" "aurora_backup_resources" {
  role       = data.aws_iam_role.aurora_backup_role.name
  policy_arn = aws_iam_policy.aurora_backup_resources.arn
}
