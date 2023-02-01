resource "aws_ecs_cluster" "online-lpa" {
  name = "${var.environment_name}-online-lpa"
  tags = local.shared_component_tag

  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  depends_on = [aws_iam_role_policy.execution_role]
}

data "aws_cloudwatch_log_group" "online-lpa" {
  name = "online-lpa"
  tags = local.shared_component_tag
}

resource "aws_iam_role" "execution_role" {
  name               = "${var.environment_name}-execution-role-ecs-cluster"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_policy.json
  tags               = local.shared_component_tag
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
  name   = "${var.environment_name}_execution_role"
  policy = data.aws_iam_policy_document.execution_role.json
  role   = aws_iam_role.execution_role.id
}

#tfsec:ignore:aws-iam-no-policy-wildcards Necessary wildcards
data "aws_iam_policy_document" "execution_role" {
  statement {
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "ecr:GetAuthorizationToken"
    ]
  }
  statement {
    effect    = "Allow"
    resources = ["arn:aws:ecr:*:*:*"]
    actions = [
      "ecr:BatchCheckLayerAvailability",
      "ecr:GetDownloadUrlForLayer",
      "ecr:BatchGetImage"
    ]
  }
  statement {
    effect    = "Allow"
    resources = ["arn:aws:ssm:*:*:*"]
    actions   = ["ssm:GetParameters"]
  }
  statement {
    effect    = "Allow"
    resources = ["arn:aws:logs:*:*:*"]
    actions = [
      "logs:CreateLogStream",
      "logs:PutLogEvents",
    ]
  }
  statement {
    effect    = "Allow"
    resources = ["arn:aws:elasticloadbalancing:*:*:*"]
    actions = [
      "elasticloadbalancing:DeregisterInstancesFromLoadBalancer",
      "elasticloadbalancing:DeregisterTargets",
      "elasticloadbalancing:DescribeAccountLimits",
      "elasticloadbalancing:DescribeListenerCertificates",
      "elasticloadbalancing:DescribeListeners",
      "elasticloadbalancing:DescribeLoadBalancerAttributes",
      "elasticloadbalancing:DescribeLoadBalancers",
      "elasticloadbalancing:DescribeRules",
      "elasticloadbalancing:DescribeSSLPolicies",
      "elasticloadbalancing:DescribeTags",
      "elasticloadbalancing:DescribeTargetGroupAttributes",
      "elasticloadbalancing:DescribeTargetGroups",
      "elasticloadbalancing:DescribeTargetHealth",
      "elasticloadbalancing:RegisterInstancesWithLoadBalancer",
      "elasticloadbalancing:RegisterTargets",
    ]
  }
  statement {
    effect = "Allow"

    actions = [
      "kms:Decrypt",
      "secretsmanager:GetSecretValue"
    ]

    resources = [
      data.aws_secretsmanager_secret.opg_lpa_common_admin_accounts.arn,
      data.aws_secretsmanager_secret.opg_lpa_common_account_cleanup_notification_recipients.arn,
      data.aws_secretsmanager_secret.opg_lpa_front_csrf_salt.arn,
      data.aws_secretsmanager_secret.opg_lpa_api_notify_api_key.arn,
      data.aws_secretsmanager_secret.opg_lpa_admin_jwt_secret.arn,
      data.aws_secretsmanager_secret.opg_lpa_front_gov_pay_key.arn,
      data.aws_secretsmanager_secret.opg_lpa_front_os_places_hub_license_key.arn,
      data.aws_secretsmanager_secret.opg_lpa_pdf_owner_password.arn,
      data.aws_secretsmanager_secret.api_rds_username.arn,
      data.aws_secretsmanager_secret.api_rds_password.arn,
      data.aws_secretsmanager_secret.performance_platform_db_username.arn,
      data.aws_secretsmanager_secret.performance_platform_db_password.arn
    ]
  }

  statement {
    effect = "Allow"

    resources = [
      data.aws_kms_alias.secrets_encryption_alias.target_key_arn,
      data.aws_kms_alias.multi_region_secrets_encryption_alias.target_key_arn
    ]

    actions = [
      "kms:Decrypt",
      "kms:GenerateDataKey",
      "kms:GenerateDataKeyPair",
      "kms:GenerateDataKeyPairWithoutPlaintext",
      "kms:GenerateDataKeyWithoutPlaintext",
      "kms:DescribeKey",
    ]
  }
}