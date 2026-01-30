
resource "aws_iam_role" "aurora_backup_role" {
  name               = "${var.environment_name}_aurora_cluster_backup_role"
  assume_role_policy = data.aws_iam_policy_document.aurora_cluster_backup_role.json
}

resource "aws_iam_role_policy_attachment" "aurora_backup_role" {
  role       = aws_iam_role.aurora_backup_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForBackup"
}


resource "aws_iam_policy" "aurora_backup_resources" {
  name        = "aurora_backup_role"
  description = "Policies for aurora backup role"
  policy      = data.aws_iam_policy_document.aurora_backup_role.json
}

resource "aws_iam_role_policy_attachment" "aurora_backup_resources" {
  role       = aws_iam_role.aurora_backup_role.name
  policy_arn = aws_iam_policy.aurora_backup_resources.arn
}



data "aws_iam_policy_document" "aurora_cluster_backup_role" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["backup.amazonaws.com"]
    }
  }
}

data "aws_iam_policy_document" "aurora_backup_role" {
  statement {
    actions = ["kms:Encrypt", "kms:Decrypt", "kms:ReEncrypt*", "kms:GenerateDataKey*", "kms:DescribeKey"]

    resources = [
      data.aws_kms_key.source_rds_snapshot_key.arn,
      data.aws_kms_key.destination_rds_snapshot_key.arn,
    ]
  }
}
