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
      # data.aws_kms_key.backup_account_key.arn,
    ]
  }
}


data "aws_kms_key" "destination_rds_snapshot_key" {
  provider = aws.destination
  key_id   = "arn:aws:kms:${var.destination_region_name}:${data.aws_caller_identity.current.account_id}:alias/${var.key_alias}"
}

data "aws_kms_key" "source_rds_snapshot_key" {
  key_id = "arn:aws:kms:${data.aws_region.current.region}:${data.aws_caller_identity.current.account_id}:alias/${var.key_alias}"
}


data "aws_iam_policy_document" "backup_account_key" {
  provider = aws.backup_account
  statement {
    sid    = "Enable Root KMS Permissions"
    effect = "Allow"
    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.backup_account.id}:root"]
    }
    actions = [
      "kms:*",
    ]
    resources = [
      "*",
    ]
  }
  statement {
    sid    = "Allow Key to be used for Encryption"
    effect = "Allow"
    resources = [
      "arn:aws:kms:*:${data.aws_caller_identity.current.account_id}:key/*"
    ]
    actions = [
      "kms:Encrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey",
    ]

    principals {
      type        = "AWS"
      identifiers = var.encryption_roles
    }
  }
  statement {
    sid    = "Allow Key to be used for Decryption"
    effect = "Allow"
    resources = [
      "arn:aws:kms:*:${data.aws_caller_identity.current.account_id}:key/*"
    ]
    actions = [
      "kms:Decrypt",
      "kms:DescribeKey",
    ]

    principals {
      type        = "AWS"
      identifiers = var.decryption_roles
    }
  }
}

# TODO - LIMIT SCOPE TO PERMISSIONS. CONFIRM IF NEEDED
data "aws_caller_identity" "backup_account" {
  provider = aws.backup_account
}
