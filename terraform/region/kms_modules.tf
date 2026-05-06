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
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  encryption_roles = [
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  grant_roles = [
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
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
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  encryption_roles = [
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  grant_roles = [
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
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
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  decryption_role_patterns = [
    "-seeding-task-role",
    "-api-task-role",
    aws_iam_role.aurora_backup_role.arn,
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.backup.account_id}:role/aws-service-role/backup.amazonaws.com/AWSServiceRoleForBackup",
  ]
  caller_accounts = [
    data.aws_caller_identity.current.account_id,
    data.aws_caller_identity.backup.account_id
  ]
}

module "secrets_manager_encryption_key" {
  source             = "git::https://github.com/ministryofjustice/opg-terraform-aws-kms-key.git?ref=v0.0.5"
  description        = "Customer managed encryption key for Secrets Manager"
  alias              = "opg-lpa-${local.account_name}-secrets-manager-encryption-key"
  usage_services     = []
  primary_region     = "eu-west-1"
  replicas_to_create = ["eu-west-2"]

  administrator_roles = [
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
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
  ]

  encryption_role_patterns = [
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/opg-lpa-ci",
  ]
  decryption_role_patterns = [
    "proxy-assume-role-",
    "execution-role-ecs-cluster",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/opg-lpa-ci",
    "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/ci",
  ]
  caller_accounts = [
    data.aws_caller_identity.current.account_id,
  ]
}
