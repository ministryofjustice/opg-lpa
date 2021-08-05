resource "aws_iam_role" "lambda_role" {
  assume_role_policy = data.aws_iam_policy_document.lambda_assume.json
  name_prefix        = var.lambda_name
  tags               = var.tags
}

data "aws_iam_policy_document" "lambda_assume" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["lambda.amazonaws.com"]
    }
  }
}

data "aws_iam_policy_document" "lambda_iam_role" {
  statement {
    sid       = "allowLogging"
    effect    = "Allow"
    resources = [aws_cloudwatch_log_group.lambda_function.arn]
    actions = [
      "logs:CreateLogStream",
      "logs:PutLogEvents",
      "logs:DescribeLogStreams"
    ]
  }

  statement {
    sid       = "AllowECRAccess"
    effect    = "Allow"
    resources = [var.ecr_arn]
    actions = [
      "ecr:SetRepositoryPolicy",
      "ecr:GetRepositoryPolicy",
      "ecr:GetDownloadUrlForLayer",
      "ecr:BatchGetImage",
      "ecr:BatchCheckLayerAvailability",
      "ecr:GetAuthorizationToken",
      "ecr:BatchGetImage",
      "ecr:DescribeImages",
      "ecr:DescribeRepositories",
      "ecr:ListImages",
      "ecr:PutImage",
      "ecr:InitiateLayerUpload",
      "ecr:UploadLayerPart",
      "ecr:CompleteLayerUpload",
    ]
  }
}

data "aws_iam_policy_document" "combined_iam_role_policy" {
  source_policy_documents = [
    data.aws_iam_policy_document.lambda_iam_role.json,
    var.lambda_role_policy_document
  ]
}
resource "aws_iam_role_policy" "lambda" {
  name   = "${var.lambda_name}-policy"
  role   = aws_iam_role.lambda_role.id
  policy = data.aws_iam_policy_document.combined_iam_role_policy.json
}

resource "aws_iam_role_policy_attachment" "vpc_access_execution_role" {
  role       = aws_iam_role.lambda_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaVPCAccessExecutionRole"
}
