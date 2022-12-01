resource "aws_db_proxy" "rds-api" {
  name          = lower("api-${var.environment_name}")
  engine_family = "POSTGRESQL"
  // Temporary - this is automatically disabled by AWS after 24 hours
  debug_logging = true
  require_tls   = true
  role_arn      = aws_iam_role.rds-api.arn
  auth {
    auth_scheme = "SECRETS"
    description = "RDS Proxy Auth"
    iam_auth    = "DISABLED"
    secret_arn  = data.aws_secretsmanager_secret.api_rds_password.arn
    username    = data.aws_secretsmanager_secret_version.api_rds_username.secret_string
  }

  vpc_security_group_ids = [aws_security_group.rds-api.id]
  vpc_subnet_ids         = data.aws_subnets.private.ids
}

resource "aws_db_proxy_default_target_group" "rds-api" {
  db_proxy_name = aws_db_proxy.rds-api.name

  connection_pool_config {
    max_connections_percent      = 100
    max_idle_connections_percent = 50
  }
}

resource "aws_db_proxy_target" "rds-api" {
  db_proxy_name         = aws_db_proxy.rds-api.name
  target_group_name     = aws_db_proxy_default_target_group.rds-api.name
  db_cluster_identifier = local.db.id
}


resource "aws_iam_role" "rds-api" {
  name               = lower("api-${var.environment_name}")
  assume_role_policy = data.aws_iam_policy_document.assume-role-rds-api.json
}

resource "aws_iam_policy" "rds-api" {
  name        = lower("api-${var.environment_name}")
  description = "RDS Proxy Policy"
  policy      = data.aws_iam_policy_document.rds-api.json
}

data "aws_iam_policy_document" "assume-role-rds-api" {
  statement {
    actions = ["sts:AssumeRole"]
    principals {
      type        = "Service"
      identifiers = ["rds.amazonaws.com"]
    }
  }
}

data "aws_iam_policy_document" "rds-api" {
  statement {
    actions = ["secretsmanager:GetSecretValue"]
    resources = [
      data.aws_secretsmanager_secret_version.api_rds_password.arn
    ]
  }

  statement {
    actions = ["kms:Decrypt"]
    resources = [
      data.aws_kms_alias.multi_region_secrets_encryption_alias.arn
    ]
  }

}

resource "aws_iam_role_policy_attachment" "rds-api" {
  role       = aws_iam_role.rds-api.name
  policy_arn = aws_iam_policy.rds-api.arn
}
