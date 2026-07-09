resource "aws_iam_role_policy" "lambda_block_ips" {
  name   = "lambda-block-ips-${data.aws_region.current.region}"
  policy = data.aws_iam_policy_document.lambda_block_ips.json
  role   = var.lambda_function_aws_iam_role.id
}

data "aws_iam_policy_document" "lambda_block_ips" {
  statement {
    sid    = "allowLogging"
    effect = "Allow"
    resources = [
      aws_cloudwatch_log_group.block_ips_lambda.arn,
      "${aws_cloudwatch_log_group.block_ips_lambda.arn}:*"
    ]
    actions = [
      "logs:CreateLogStream",
      "logs:PutLogEvents",
      "logs:DescribeLogStreams"
    ]
  }

  statement {
    sid    = "ReadLogsAndInsights"
    effect = "Allow"
    actions = [
      "logs:GetLogEvents",
      "logs:StartQuery",
      "logs:StopQuery",
      "logs:GetQueryResults",
    ]
    resources = ["*"]
  }

  statement {
    sid    = "ReadWriteTable"
    effect = "Allow"
    resources = [
      aws_dynamodb_table.blocked_ips_table.arn,
    ]
    actions = [
      "dynamodb:BatchGet*",
      "dynamodb:DescribeStream",
      "dynamodb:DescribeTable",
      "dynamodb:Get*",
      "dynamodb:Query",
      "dynamodb:Scan",
      "dynamodb:BatchWrite*",
      "dynamodb:Delete*",
      "dynamodb:Update*",
      "dynamodb:PutItem"
    ]
  }

  statement {
    sid    = "UpdateIPSet"
    effect = "Allow"
    actions = [
      "wafv2:ListIPSets",
      "wafv2:GetIPSet",
      "wafv2:UpdateIPSet"
    ]
    resources = ["*"]
  }

  statement {
    sid    = "UseDynamodbKMSKey"
    effect = "Allow"
    actions = [
      "kms:Encrypt",
      "kms:Decrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey"
    ]
    resources = [
      var.dynamodb_kms_key_arn
    ]
  }
}
