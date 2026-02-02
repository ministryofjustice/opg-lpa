data "aws_kms_key" "destination_rds_snapshot_key" {
  provider = aws.destination
  key_id   = "arn:aws:kms:${var.destination_region_name}:${data.aws_caller_identity.current.account_id}:alias/${var.key_alias}"
}

data "aws_kms_key" "source_rds_snapshot_key" {
  key_id = "arn:aws:kms:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:alias/${var.key_alias}"
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

resource "aws_iam_role_policy_attachment" "kms_aurora_backup_role" {
  role       = aws_iam_role.aurora_backup_role.name
  policy_arn = aws_iam_policy.kms_aurora_backup_role.arn
}

resource "aws_iam_policy" "kms_aurora_backup_role" {
  name        = "kms_aurora_backup_role"
  description = "KMS policy for aurora backup role"
  policy      = data.aws_iam_policy_document.aurora_backup_role.json
}

resource "aws_iam_role" "aurora_backup_role" {
  name               = "aurora_cluster_backup_role"
  assume_role_policy = data.aws_iam_policy_document.aurora_cluster_backup_role.json
}

resource "aws_iam_role_policy_attachment" "aurora_backup_role" {
  role       = aws_iam_role.aurora_backup_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForBackup"
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
