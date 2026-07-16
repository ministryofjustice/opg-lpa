resource "aws_iam_role" "ip_blocker" {
  assume_role_policy = data.aws_iam_policy_document.ip_blocker_policy.json
  name               = "lambda-block-ips"
}

data "aws_iam_policy_document" "ip_blocker_policy" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = ["lambda.amazonaws.com"]
      type        = "Service"
    }
  }
}
