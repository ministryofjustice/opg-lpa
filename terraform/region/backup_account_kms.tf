resource "aws_kms_key" "backup_account_key" {
  description             = "cross account backup encryption key"
  deletion_window_in_days = 7
  enable_key_rotation     = true
  provider                = aws.backup_account

  policy = data.aws_iam_policy_document.backup_account_key.policy
}

resource "aws_kms_alias" "backup_account_key" {
  name          = "alias/mrk-rds-cross-account-backup-key"
  target_key_id = aws_kms_key.backup_account_key.key_id
  provider      = aws.backup_account
}

data "aws_iam_policy_document" "backup_account_key" {
  provider = aws.backup_account
  statement {
    sid       = "Enable Root account permissions on Key"
    effect    = "Allow"
    actions   = ["kms:*"]
    resources = ["*"]

    principals {
      type = "AWS"
      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.backup_account.account_id}:root",
      ]
    }
  }

  statement {
    sid       = "AllowAWSBackupServiceAccess"
    resources = ["*"]
    actions = [
      "kms:CreateGrant",
      "kms:Encrypt",
      "kms:Decrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey"
    ]

    principals {
      type        = "Service"
      identifiers = ["backup.amazonaws.com"]
    }
  }

  statement {
    sid       = "AllowSourceAccountBackupServiceAccess"
    resources = ["*"]
    actions = [
      "kms:Encrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey"
    ]

    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"]
    }
  }
  statement {
    sid       = "Key Administrator"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Create*",
      "kms:Describe*",
      "kms:Enable*",
      "kms:List*",
      "kms:Put*",
      "kms:Update*",
      "kms:Revoke*",
      "kms:Disable*",
      "kms:Get*",
      "kms:Delete*",
      "kms:TagResource",
      "kms:UntagResource",
      "kms:ScheduleKeyDeletion",
      "kms:CancelKeyDeletion"
    ]

    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.backup_account.account_id}:role/breakglass"]
    }
  }
}
