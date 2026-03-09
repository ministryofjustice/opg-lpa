resource "aws_iam_role" "dms_vpc_management" {
  count              = var.create_iam_roles ? 1 : 0
  name               = "dms-vpc-role"
  assume_role_policy = data.aws_iam_policy_document.dms_assume_role.json

  tags = merge(
    local.common_tags,
    {
      Resource_Type = "Role having DMS VPC access policies"
    }
  )
}

resource "aws_iam_role_policy_attachment" "dms_vpc_management" {
  count      = var.create_iam_roles ? 1 : 0
  role       = aws_iam_role.dms_vpc_management[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonDMSVPCManagementRole"
}

resource "aws_iam_role" "dms_cloudwatch_logs" {
  count              = var.create_iam_roles ? 1 : 0
  name               = "dms-cloudwatch-logs-role"
  assume_role_policy = data.aws_iam_policy_document.dms_assume_role.json

  tags = merge(
    local.common_tags,
    {
      Resource_Type = "Role having DMS CloudWatch Logs access policies"
    }
  )
}

resource "aws_iam_role_policy_attachment" "dms_cloudwatch_logs" {
  count      = var.create_iam_roles ? 1 : 0
  role       = aws_iam_role.dms_cloudwatch_logs[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonDMSCloudWatchLogsRole"
}

data "aws_iam_policy_document" "dms_kms_access" {
  statement {
    actions = [
      "kms:Encrypt",
      "kms:Decrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey",
      "kms:CreateGrant"
    ]

    resources = [var.replication_instance.kms_key_arn]

    condition {
      test     = "StringEquals"
      variable = "kms:ViaService"
      values   = ["dms.${data.aws_region.current.id}.amazonaws.com"]
    }

    condition {
      test     = "StringEquals"
      variable = "kms:CallerAccount"
      values   = [data.aws_caller_identity.current.account_id]
    }
  }
}

resource "aws_iam_policy" "dms_kms_access" {
  count       = var.create_iam_roles ? 1 : 0
  name        = "aurora-${var.environment_name}-dms-kms-access-policy"
  description = "Least-privilege KMS permissions for DMS replication encryption"
  policy      = data.aws_iam_policy_document.dms_kms_access.json
}

resource "aws_iam_role_policy_attachment" "dms_kms_access" {
  count      = var.create_iam_roles ? 1 : 0
  role       = aws_iam_role.dms_vpc_management[0].name
  policy_arn = aws_iam_policy.dms_kms_access[0].arn
}
