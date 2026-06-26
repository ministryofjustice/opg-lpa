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
  role_arn = var.rds_proxy_iam_role.arn

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

data "aws_iam_policy_document" "rds_proxy_role" {
  policy_id = "rdsproxy${var.environment_name}${replace(data.aws_region.current.region, "-", "")}"
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
    resources = var.secretsmanager_encryption_key_arns
  }
}

resource "aws_iam_role_policy" "rds_proxy" {
  name   = lower("rds-proxy-role-policy-${var.environment_name}-${data.aws_region.current.region}")
  role   = var.rds_proxy_iam_role.id
  policy = data.aws_iam_policy_document.rds_proxy_role.json
}

resource "aws_security_group" "rds_proxy" {
  name_prefix            = "rds-proxy-${var.environment_name}"
  description            = "RDS access from RDS Proxy"
  vpc_id                 = var.vpc_id
  revoke_rules_on_delete = true
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "client_to_proxy_ingress" {
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = var.rds_client_security_group_id
  security_group_id        = aws_security_group.rds_proxy.id
  description              = "Ingress from rds client"
}

resource "aws_security_group_rule" "proxy_to_cluster_egress" {
  type                     = "egress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = var.rds_api_security_group_id
  security_group_id        = aws_security_group.rds_proxy.id
  description              = "Egress To RDS"
}

resource "aws_security_group_rule" "cluster_from_proxy_ingress" {
  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  source_security_group_id = aws_security_group.rds_proxy.id
  security_group_id        = var.rds_api_security_group_id
  description              = "Egress To RDS"
}

resource "aws_security_group_rule" "rds_proxy_secrets_manager" {
  for_each                 = data.aws_vpc_endpoint.secrets_manager.security_group_ids
  type                     = "egress"
  from_port                = 443
  to_port                  = 443
  protocol                 = "tcp"
  source_security_group_id = each.value
  security_group_id        = aws_security_group.rds_proxy.id
  description              = "Egress For Secrets Manager"
}
