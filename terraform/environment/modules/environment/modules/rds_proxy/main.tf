resource "aws_db_proxy" "rds_proxy" {
  name                = lower("proxy-${var.environment_name}")
  debug_logging       = true # this may uncover sensitive information in the logs - but it shouldn't
  engine_family       = "POSTGRESQL"
  idle_client_timeout = 1800
  require_tls         = true
  vpc_subnet_ids      = var.vpc_subnet_ids
  vpc_security_group_ids = [
    aws_security_group.rds_proxy.id,
  ]
  role_arn = aws_iam_role.rds_proxy_role.arn

  auth {
    auth_scheme               = "SECRETS"
    description               = "Authentication for RDS Proxy"
    iam_auth                  = "DISABLED"
    client_password_auth_type = "POSTGRES_MD5" # pragma: allowlist secret
    secret_arn                = var.api_rds_credentials_secret_arn
  }
}

resource "aws_db_proxy_default_target_group" "rds" {
  db_proxy_name = aws_db_proxy.rds_proxy.name

  connection_pool_config {
    connection_borrow_timeout    = 120
    init_query                   = ""
    max_connections_percent      = 100
    max_idle_connections_percent = 50
    session_pinning_filters      = []
  }
}

resource "aws_db_proxy_target" "rds" {
  db_proxy_name         = aws_db_proxy.rds_proxy.name
  target_group_name     = aws_db_proxy_default_target_group.rds.name
  db_cluster_identifier = var.db_cluster_identifier
}

resource "aws_iam_role" "rds_proxy_role" {
  name               = lower("proxy-assume-role-${var.environment_name}")
  assume_role_policy = data.aws_iam_policy_document.rds_proxy_assume.json
}

data "aws_iam_policy_document" "rds_proxy_assume" {
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

data "aws_iam_policy_document" "rds_proxy_role" {
  statement {
    sid    = "RDSSecretsManagerAccess"
    effect = "Allow"
    actions = [
      "secretsmanager:GetSecretValue"
    ]

    resources = [
      var.api_rds_credentials_secret_arn,
    ]
  }

  statement {
    sid    = "AllowRDSKMS"
    effect = "Allow"
    actions = [
      "kms:Decrypt"
    ]
    resources = [var.secretsmanager_encryption_key_arn]
  }
}

resource "aws_iam_role_policy" "rds_proxy" {
  name   = lower("rds-proxy-role-policy-${var.environment_name}")
  role   = aws_iam_role.rds_proxy_role.id
  policy = data.aws_iam_policy_document.rds_proxy_role.json
}

resource "aws_security_group" "rds_proxy" {
  name                   = "rds-proxy-${var.environment_name}"
  description            = "RDS access from RDS Proxy"
  vpc_id                 = var.vpc_id
  revoke_rules_on_delete = true
}

resource "aws_security_group_rule" "rds_proxy_ingress" {
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = var.rds_client_security_group_id
  security_group_id        = aws_security_group.rds_proxy.id
  description              = "Ingress from rds client"
}

resource "aws_security_group_rule" "rds_proxy_rds_egress" {
  type                     = "egress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = var.rds_api_security_group_id
  security_group_id        = aws_security_group.rds_proxy.id
  description              = "Egress To RDS"
}

resource "aws_security_group_rule" "rds_proxy_rds_ingress" {
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.rds_proxy.id
  security_group_id        = var.rds_api_security_group_id
  description              = "Egress To RDS"
}

resource "aws_security_group_rule" "rds_proxy_secrets_manager" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.rds_proxy.id
  description       = "Egress For Secrets Manager"
}
