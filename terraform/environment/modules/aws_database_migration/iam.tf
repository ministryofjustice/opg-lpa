resource "aws_iam_role" "dms_vpc_management" {
  count = var.create_iam_roles ? 1 : 0

  name = "aurora-${var.environment_name}-dms-vpc-role"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect    = "Allow"
        Action    = "sts:AssumeRole"
        Principal = { Service = "dms.amazonaws.com" }
      }
    ]
  })

  tags = local.common_tags
}

resource "aws_iam_role_policy_attachment" "dms_vpc_management" {
  count = var.create_iam_roles ? 1 : 0

  role       = aws_iam_role.dms_vpc_management[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonDMSVPCManagementRole"
}

resource "aws_iam_role" "dms_cloudwatch_logs" {
  count = var.create_iam_roles ? 1 : 0

  name = "aurora-${var.environment_name}-dms-cw-logs-role"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect    = "Allow"
        Action    = "sts:AssumeRole"
        Principal = { Service = "dms.amazonaws.com" }
      }
    ]
  })

  tags = local.common_tags
}

resource "aws_iam_role_policy_attachment" "dms_cloudwatch_logs" {
  count = var.create_iam_roles ? 1 : 0

  role       = aws_iam_role.dms_cloudwatch_logs[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonDMSCloudWatchLogsRole"
}

resource "aws_iam_policy" "dms_kms_access" {
  count = var.create_iam_roles ? 1 : 0

  name        = "aurora-${var.environment_name}-dms-kms"
  description = "Allow DMS to use the replication CMK"
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "kms:Encrypt",
          "kms:Decrypt",
          "kms:GenerateDataKey*",
          "kms:DescribeKey"
        ],
        Resource = [data.aws_kms_key.replication.arn]
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "dms_kms_access" {
  count = var.create_iam_roles ? 1 : 0

  role       = aws_iam_role.dms_vpc_management[0].name
  policy_arn = aws_iam_policy.dms_kms_access[0].arn
}
