data "aws_caller_identity" "current" {}

data "aws_region" "current" {}

data "aws_iam_policy_document" "dms_assume_role" {
  statement {
    sid     = "AllowDMSServiceAssumeRole"
    actions = ["sts:AssumeRole"]
    effect  = "Allow"

    principals {
      type        = "Service"
      identifiers = ["dms.amazonaws.com"]
    }

    condition {
      test     = "StringEquals"
      variable = "aws:SourceAccount"
      values   = [data.aws_caller_identity.current.account_id]
    }
  }
}

data "aws_rds_cluster" "source" {
  cluster_identifier = var.source_config.cluster_identifier
}

data "aws_rds_cluster" "target" {
  cluster_identifier = var.target_config.cluster_identifier
}

data "aws_secretsmanager_secret_version" "source_db_username" {
  secret_id = var.source_config.username_secret_name
}

data "aws_secretsmanager_secret_version" "source_db_password" {
  secret_id = var.source_config.password_secret_name
}

data "aws_secretsmanager_secret_version" "target_db_username" {
  secret_id = var.target_config.username_secret_name
}

data "aws_secretsmanager_secret_version" "target_db_password" {
  secret_id = var.target_config.password_secret_name
}
