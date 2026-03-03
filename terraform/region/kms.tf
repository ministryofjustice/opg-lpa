module "aws_backup_cross_account_key" {
  source             = "git::https://github.com/ministryofjustice/opg-terraform-aws-kms-key.git?ref=v0.0.5"
  description        = "Encryption keys for Make an LPA backups copied into the backup account"
  alias              = "opg-lpa-${local.account_name}-aws-backup-key"
  primary_region     = "eu-west-1"
  replicas_to_create = []
  providers = {
    aws = aws.backup
  }
  usage_services = ["backup.amazonaws.com"]

  administrator_roles = [
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/opg-lpa-ci",
  ]
  decryption_roles = [
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/breakglass",
    "arn:aws:${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  encryption_roles = [
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/breakglass",
    "arn:aws:${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  grant_roles = [
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/breakglass",
    "arn:aws:${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
}

module "aws_backup_source_account_key" {
  source             = "git::https://github.com/ministryofjustice/opg-terraform-aws-kms-key.git?ref=v0.0.5"
  description        = "Encryption keys for Make an LPA backups copied into the backup account"
  alias              = "opg-lpa-${local.account_name}-aws-backup-source-account-key"
  primary_region     = "eu-west-1"
  replicas_to_create = ["eu-west-2"]
  providers = {
    aws = aws.eu-west-1
  }
  usage_services = ["backup.*.amazonaws.com"]

  administrator_roles = [
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/opg-lpa-ci",
  ]
  decryption_roles = [
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    aws_iam_role.aurora_backup_role.arn,
  ]
  encryption_roles = [
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    aws_iam_role.aurora_backup_role.arn,
  ]
  grant_roles = [
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass"
  ]
}

module "aurora_database_encryption_key" {
  source      = "git::https://github.com/ministryofjustice/opg-terraform-aws-kms-key.git?ref=v0.0.5"
  description = "Customer managed encryption key for Aurora RDS database"
  alias       = "opg-lpa-${local.account_name}-rds-encryption-key"
  usage_services = [
    "rds.*.amazonaws.com",
    "backup.*.amazonaws.com",
  ]
  primary_region     = "eu-west-1"
  replicas_to_create = ["eu-west-2"]

  administrator_roles = [
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/opg-lpa-ci",
  ]
  decryption_roles = [
    "*",
  ]
  encryption_roles = [
    "*",
  ]
  grant_roles = [
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    aws_iam_role.aurora_backup_role.arn,
  ]

  encryption_role_patterns = [
    "-seeding-task-role",
    "-api-task-role",
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  decryption_role_patterns = [
    "-seeding-task-role",
    "-api-task-role",
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  caller_accounts = [
    data.aws_caller_identity.current.account_id,
    data.aws_caller_identity.backup.account_id
  ]
}
# old aws backup kms keys - to be removed once all backup resources have been migrated to the new key
resource "aws_kms_key" "multi_region_db_snapshot_key" {
  enable_key_rotation = true
  provider            = aws.eu-west-1
  multi_region        = true
}

resource "aws_kms_alias" "multi_region_db_snapshot_alias" {
  name          = "alias/mrk_db_snapshot_key-${terraform.workspace}"
  target_key_id = aws_kms_key.multi_region_db_snapshot_key.key_id
}

resource "aws_kms_replica_key" "multi_region_db_snapshot_key_replica" {
  description             = "db snapshot replica key"
  deletion_window_in_days = 7
  primary_key_arn         = aws_kms_key.multi_region_db_snapshot_key.arn
  provider                = aws.eu-west-2
}

resource "aws_kms_alias" "multi_region_db_snapshot_alias_replica" {
  name          = "alias/mrk_db_snapshot_key-${terraform.workspace}"
  target_key_id = aws_kms_replica_key.multi_region_db_snapshot_key_replica.key_id
  provider      = aws.eu-west-2
}


data "aws_kms_key" "access_log_key" {
  key_id = "alias/mrk_access_logs_lb_encryption_key-${terraform.workspace}"
}

data "aws_iam_policy_document" "s3_loadbalancer_kms" {

  statement {
    sid       = "Enable Root account permissions on Key"
    effect    = "Allow"
    actions   = ["kms:*"]
    resources = ["*"]

    principals {
      type = "AWS"
      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root",
      ]
    }
  }

  statement {
    sid       = "Key Administrator"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Create*",
      "kms:Describe*",
      "kms:Enable*",
      "kms:List*",
      "kms:Put*",
      "kms:Update*",
      "kms:Revoke*",
      "kms:Disable*",
      "kms:Get*",
      "kms:Delete*",
      "kms:TagResource",
      "kms:UntagResource",
      "kms:ScheduleKeyDeletion",
      "kms:CancelKeyDeletion"
    ]

    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass"]
    }
  }

  statement {
    sid    = "Allow ELB to use Key for Encryption"
    effect = "Allow"

    actions = [
      "kms:Encrypt",
      "kms:Decrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey"
    ]

    resources = [
      data.aws_kms_key.access_log_key.arn,
    ]

    principals {
      identifiers = ["delivery.logs.amazonaws.com"]

      type = "Service"
    }
  }
}

resource "aws_kms_key" "multi_region_access_logs_lb_encryption_key" {
  enable_key_rotation = true
  multi_region        = true
  provider            = aws.eu-west-1
  policy              = data.aws_iam_policy_document.s3_loadbalancer_kms.json
}

resource "aws_kms_alias" "multi_region_access_logs_lb_encryption_alias" {
  name          = "alias/mrk_access_logs_lb_encryption_key-${terraform.workspace}"
  target_key_id = aws_kms_key.multi_region_access_logs_lb_encryption_key.key_id
  provider      = aws.eu-west-1
}

resource "aws_kms_replica_key" "multi_region_access_logs_lb_encryption_key_replica" {
  description             = "Loadbalancer access log encryption replica key"
  deletion_window_in_days = 7
  primary_key_arn         = aws_kms_key.multi_region_access_logs_lb_encryption_key.arn
  provider                = aws.eu-west-2
  policy                  = data.aws_iam_policy_document.s3_loadbalancer_kms.json
}

resource "aws_kms_alias" "multi_region_access_logs_lb_encryption_alias_replica" {
  name          = "alias/mrk_access_logs_lb_encryption_key-${terraform.workspace}"
  target_key_id = aws_kms_replica_key.multi_region_access_logs_lb_encryption_key_replica.key_id
  provider      = aws.eu-west-2
}

resource "aws_kms_key" "multi_region_pdf_sqs_encryption_key" {
  enable_key_rotation = true
  multi_region        = true
  provider            = aws.eu-west-1
}

resource "aws_kms_alias" "multi_region_pdf_sqs_encryption_alias" {
  name          = "alias/mrk_pdf_sqs_encryption_key-${terraform.workspace}"
  target_key_id = aws_kms_key.multi_region_pdf_sqs_encryption_key.key_id
  provider      = aws.eu-west-1
}

resource "aws_kms_replica_key" "multi_region_pdf_sqs_encryption_key_replica" {
  description             = "PDF SQS encryption replica key"
  deletion_window_in_days = 7
  primary_key_arn         = aws_kms_key.multi_region_pdf_sqs_encryption_key.arn
  provider                = aws.eu-west-2
}

resource "aws_kms_alias" "multi_region_pdf_sqs_encryption_alias_replica" {
  name          = "alias/mrk_pdf_sqs_encryption_key-${terraform.workspace}"
  target_key_id = aws_kms_replica_key.multi_region_pdf_sqs_encryption_key_replica.key_id
  provider      = aws.eu-west-2
}

resource "aws_kms_key" "multi_region_secrets_encryption_key" {
  enable_key_rotation = true
  provider            = aws.eu-west-1
  multi_region        = true
}

resource "aws_kms_alias" "multi_region_secrets_encryption_alias" {
  name          = "alias/mrk_secrets_encryption_key-${terraform.workspace}"
  target_key_id = aws_kms_key.multi_region_secrets_encryption_key.key_id
}

resource "aws_kms_replica_key" "multi_region_secrets_encryption_key_replica" {
  description             = "secrets encryption replica key"
  deletion_window_in_days = 7
  primary_key_arn         = aws_kms_key.multi_region_secrets_encryption_key.arn
  provider                = aws.eu-west-2
}

resource "aws_kms_alias" "multi_region_secrets_encryption_alias_replica" {
  name          = "alias/mrk_secrets_encryption_key-${terraform.workspace}"
  target_key_id = aws_kms_replica_key.multi_region_secrets_encryption_key_replica.key_id
  provider      = aws.eu-west-2
}
