resource "aws_iam_role" "dms_vpc_role" {
  count              = var.create_iam_roles ? 1 : 0
  provider           = aws.eu_west_1
  name               = "aurora-${var.environment_name}-dms-vpc-role"
  assume_role_policy = data.aws_iam_policy_document.dms_vpc_role_permissions.json
}

resource "aws_iam_role_policy_attachment" "dms_vpc_role" {
  count      = var.create_iam_roles ? 1 : 0
  provider   = aws.eu_west_1
  role       = aws_iam_role.dms_vpc_role[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonDMSVPCManagementRole"
}
data "aws_iam_policy_document" "dms_vpc_role_permissions" {
  statement {
    sid     = "AllowAWSServicesToAssumeRole"
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = [
        "dms.amazonaws.com",
        "dms.eu-west-1.amazonaws.com",
      ]
      type = "Service"
    }
  }
}

# dms cloudwatch role
resource "aws_iam_role" "dms_cloudwatch_role" {
  count    = var.create_iam_roles ? 1 : 0
  provider = aws.eu_west_1

  name = "aurora-${var.environment_name}-dms-cloudwatch-role"
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

resource "aws_iam_role_policy_attachment" "dms_cloudwatch_role" {
  count      = var.create_iam_roles ? 1 : 0
  provider   = aws.eu_west_1
  role       = aws_iam_role.dms_cloudwatch_role[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonDMSCloudWatchLogsRole"
}
