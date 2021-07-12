resource "aws_ecs_cluster" "online-lpa" {
  name = "${local.environment}-online-lpa"
  tags = merge(local.default_tags, local.shared_component_tag)

  setting {
    name  = "containerInsights"
    value = "enabled"
  }
}

data "aws_cloudwatch_log_group" "online-lpa" {
  name = "online-lpa"
  tags = merge(local.default_tags, local.shared_component_tag)
}

resource "aws_iam_role" "execution_role" {
  name               = "${local.environment}-execution-role-ecs-cluster"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = merge(local.default_tags, local.shared_component_tag)
}

data "aws_iam_policy_document" "ecs_assume_policy" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = ["ecs-tasks.amazonaws.com"]
      type        = "Service"
    }
  }
}

resource "aws_iam_role_policy" "execution_role" {
  name   = "${local.environment}_execution_role"
  policy = data.aws_iam_policy_document.execution_role.json
  role   = aws_iam_role.execution_role.id
}

data "aws_iam_policy_document" "execution_role" {
  statement {
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "ecr:GetAuthorizationToken",
      "ecr:BatchCheckLayerAvailability",
      "ecr:GetDownloadUrlForLayer",
      "ecr:BatchGetImage",
      "logs:CreateLogStream",
      "logs:PutLogEvents",
    ]
  }
  statement {
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "elasticloadbalancing:DeregisterInstancesFromLoadBalancer",
      "elasticloadbalancing:DeregisterTargets",
      "elasticloadbalancing:Describe*",
      "elasticloadbalancing:RegisterInstancesWithLoadBalancer",
      "elasticloadbalancing:RegisterTargets",
    ]
  }
  statement {
    effect    = "Allow"
    resources = ["*"]
    actions   = ["ssm:GetParameters"]
  }
  statement {
    effect = "Allow"

    actions = [
      "kms:Decrypt",
      "secretsmanager:GetSecretValue",
    ]

    resources = ["*"]
  }
}
