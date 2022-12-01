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
    secret_arn  = aws_secretsmanager_secret.lambda_rds_test_proxy_creds.arn
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
      aws_secretsmanager_secret.lambda_rds_test_proxy_creds.arn
    ]
  }

}

resource "aws_iam_role_policy_attachment" "rds-api" {
  role       = aws_iam_role.rds-api.name
  policy_arn = aws_iam_policy.rds-api.arn
}

resource "aws_secretsmanager_secret" "lambda_rds_test_proxy_creds" {
  name = lower("lambda-rds-test-proxy-creds-${var.environment_name}")
}

resource "aws_secretsmanager_secret_version" "lambda_rds_test_proxy_creds" {
  secret_id = aws_secretsmanager_secret.lambda_rds_test_proxy_creds.id
  secret_string = jsonencode({
    "username"             = local.db.username
    "password"             = data.aws_secretsmanager_secret_version.api_rds_password.secret_string
    "engine"               = "postgres"
    "host"                 = local.db.endpoint
    "port"                 = local.db.port
    "dbInstanceIdentifier" = local.db.id
  })
}
