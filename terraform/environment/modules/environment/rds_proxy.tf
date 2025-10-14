resource "aws_db_proxy" "rds_proxy" {
  count               = var.account.rds_proxy_enabled ? 1 : 0
  name                = lower("proxy-${var.account_name}")
  debug_logging       = true # this may uncover sensitive information in the logs - but it shouldn't
  engine_family       = "POSTGRESQL"
  idle_client_timeout = 1800
  require_tls         = true
  vpc_subnet_ids      = data.aws_subnets.private.ids
  role_arn            = aws_iam_role.rds_proxy_role[0].arn

  auth {
    auth_scheme = "SECRETS"
    description = "Authentication for RDS Proxy"
    iam_auth    = "DISABLED"
    username    = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
    secret_arn  = data.aws_secretsmanager_secret_version.api_rds_password.arn
  }
}

resource "aws_iam_role" "rds_proxy_role" {
  count              = var.account.rds_proxy_enabled ? 1 : 0
  name               = lower("proxy-assume-role-${var.environment_name}")
  assume_role_policy = data.aws_iam_policy_document.rds_proxy_assume.json
}

data "aws_iam_policy_document" "rds_proxy_assume" {
  statement {
    sid = "AllowRDSServiceAssumeRole"

    actions = ["sts:AssumeRole"]

    effect = "Allow"

    principals {
      type        = "Service"
      identifiers = ["rds.amazonaws.com"]
    }
  }
}

data "aws_iam_policy_document" "rds_proxy_role" {
  statement {
    sid    = "RDSSecretsManagerAccess"
    effect = "Allow"
    actions = [
      "secretsmanager:GetSecretValue",
      "kms:Decrypt"
    ]

    resources = [
      data.aws_secretsmanager_secret.api_rds_username.arn,
      data.aws_secretsmanager_secret.api_rds_password.arn
    ]
  }

  statement {
    sid    = "AllowRDSKMS"
    effect = "Allow"
    actions = [
      "kms:DescribeKey",
      "kms:Decrypt"
    ]
    resources = [data.aws_kms_alias.secrets_encryption_alias.target_key_arn]
  }
}

data "aws_iam_policy_document" "combined_iam_role_policy" {
  source_policy_documents = [
    data.aws_iam_policy_document.rds_proxy_assume.json,
    data.aws_iam_policy_document.rds_proxy_role.json
  ]
}

resource "aws_iam_role_policy" "rds_proxy" {
  name   = lower("rds-proxy-role-policy-${var.environment_name}")
  role   = aws_iam_role.rds_proxy_role[0].id
  policy = data.aws_iam_policy_document.combined_iam_role_policy.json
}
