resource "aws_iam_role" "rds_proxy" {
  name                 = lower("rds-proxy-${local.environment_name}")
  assume_role_policy   = data.aws_iam_policy_document.rds_proxy.json
  permissions_boundary = data.aws_iam_policy.default_boundary.arn
}

data "aws_iam_policy_document" "rds_proxy" {
  statement {
    sid     = "AllowRDSServiceAssumeRole"
    actions = ["sts:AssumeRole"]
    effect  = "Allow"

    principals {
      type        = "Service"
      identifiers = ["rds.amazonaws.com"]
    }
  }
}
