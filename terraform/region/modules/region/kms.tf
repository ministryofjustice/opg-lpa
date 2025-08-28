resource "aws_kms_key" "secrets_encryption_key" {
  enable_key_rotation = true
}

resource "aws_kms_alias" "secrets_encryption_alias" {
  name          = "alias/secrets_encryption_key-${terraform.workspace}"
  target_key_id = aws_kms_key.secrets_encryption_key.key_id
}


resource "aws_kms_key" "lpa_pdf_cache" {
  description             = "S3 bucket encryption key for lpa_pdf_cache"
  deletion_window_in_days = 7
  tags                    = local.pdf_component_tag
  enable_key_rotation     = true
}

resource "aws_kms_alias" "lpa_pdf_cache" {
  name          = "alias/lpa_pdf_cache-${terraform.workspace}"
  target_key_id = aws_kms_key.lpa_pdf_cache.key_id
}

resource "aws_kms_key" "redacted_logs" {
  description             = "S3 bucket encryption key for redacted_logs"
  deletion_window_in_days = 7
  enable_key_rotation     = true
}

resource "aws_kms_alias" "redacted_logs" {
  name          = "alias/redacted_logs-${terraform.workspace}"
  target_key_id = aws_kms_key.redacted_logs.key_id
}

# See the following link for further information
# https://docs.aws.amazon.com/kms/latest/developerguide/key-policies.html
resource "aws_kms_key" "cloudwatch_encryption" {
  description             = "encryption key for cloudwatch"
  deletion_window_in_days = 7
  tags                    = local.pdf_component_tag
  enable_key_rotation     = true
  policy                  = data.aws_iam_policy_document.cloudwatch_encryption_kms.json

}

resource "aws_kms_alias" "cloudwatch_encryption" {
  name          = "alias/cloudwatch_encryption-${terraform.workspace}"
  target_key_id = aws_kms_key.cloudwatch_encryption.key_id
}


data "aws_iam_policy_document" "cloudwatch_encryption_kms" {
  statement {
    sid       = "Enable Root account permissions on Key"
    effect    = "Allow"
    actions   = ["kms:*"]
    resources = ["*"]

    principals {
      type = "AWS"
      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root",
      ]
    }
  }


  statement {
    sid       = "Allow Key to be used for Encryption"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Encrypt",
      "kms:Decrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey",
    ]

    principals {
      type = "Service"
      identifiers = [
        "logs.${data.aws_region.current.region}.amazonaws.com",
        "events.amazonaws.com"
      ]
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
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass"]
    }
  }
}
