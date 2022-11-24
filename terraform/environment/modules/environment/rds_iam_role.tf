resource "aws_iam_role" "rds_iam" {
  assume_role_policy = data.aws_iam_policy_document.assume_rds_iam.json
  name               = "${var.environment_name}-rds-iam"
}


resource "aws_iam_role_policy" "rds_iam" {
  name   = "${var.environment_name}-rds-iam"
  policy = data.aws_iam_policy_document.rds_iam.json
  role   = aws_iam_role.rds_iam.id
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

data "aws_iam_policy_document" "assume_rds_iam" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = ["ecs-tasks.amazonaws.com"]
      type        = "Service"
    }
  }
}
