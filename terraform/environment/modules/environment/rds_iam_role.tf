resource "aws_iam_role" "rds_iam" {
  assume_role_policy = data.aws_iam_policy_document.rds_iam.json
  name               = "${var.environment_name}-rds-iam"
  tags               = local.default_tags
}

data "aws_iam_policy_document" "rds_iam" {
  statement {
    sid    = "ConnectRDS"
    effect = "Allow"

    actions = [
      "rds-db:connect",
    ]

    resources = ["arn:aws:rds-db:eu-west-1:050256574573:dbuser:*/*"]
  }
}