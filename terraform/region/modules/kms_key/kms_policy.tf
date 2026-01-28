
data "aws_iam_policy_document" "kms_key" {
  override_policy_documents = var.custom_addition_permissions != "" ? [var.custom_addition_permissions] : []
  statement {
    sid    = "Enable Root KMS Permissions"
    effect = "Allow"
    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"]
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

    dynamic "condition" {
      for_each = length(var.usage_services) > 0 ? [1] : []
      content {
        test     = "StringLike"
        variable = "kms:ViaService"

        values = var.usage_services
      }
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

    dynamic "condition" {
      for_each = length(var.usage_services) > 0 ? [1] : []
      content {
        test     = "StringLike"
        variable = "kms:ViaService"

        values = var.usage_services
      }
    }
  }

  statement {
    sid    = "General View Access"
    effect = "Allow"
    resources = [
      "arn:aws:kms:*:${data.aws_caller_identity.current.account_id}:key/*"
    ]
    actions = [
      "kms:DescribeKey",
      "kms:GetKeyPolicy",
      "kms:GetKeyRotationStatus",
      "kms:List*",
    ]

    principals {
      type = "AWS"
      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
      ]
    }
  }

  statement {
    sid    = "Key Administrator"
    effect = "Allow"
    resources = [
      "arn:aws:kms:*:${data.aws_caller_identity.current.account_id}:key/*"
    ]
    actions = [
      "kms:Create*",
      "kms:Describe*",
      "kms:Decrypt",
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
      "kms:CancelKeyDeletion",
      "kms:ReplicateKey",
    ]

    principals {
      type        = "AWS"
      identifiers = var.administrator_roles
    }
  }
}
