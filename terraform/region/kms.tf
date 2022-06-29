resource "aws_kms_key" "multi_region_access_logs_lb_encryption_key" {
  enable_key_rotation = true
  multi_region        = true
  provider            = aws.eu-west-1
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

resource "aws_kms_key" "multi_region_perfplat_sqs_encryption_key" {
  enable_key_rotation = true
  multi_region        = true
  provider            = aws.eu-west-1
}

resource "aws_kms_alias" "multi_region_perfplat_sqs_encryption_alias" {
  name          = "alias/mrk_perfplat_sqs_encryption_key-${terraform.workspace}"
  target_key_id = aws_kms_key.multi_region_perfplat_sqs_encryption_key.key_id
  provider      = aws.eu-west-1
}

resource "aws_kms_replica_key" "multi_region_perfplat_sqs_encryption_key_replica" {
  description             = "Performance Platform SQS encryption replica key"
  deletion_window_in_days = 7
  primary_key_arn         = aws_kms_key.multi_region_perfplat_sqs_encryption_key.arn
  provider                = aws.eu-west-2
}

resource "aws_kms_alias" "multi_region_perfplat_sqs_encryption_alias_replica" {
  name          = "alias/mrk_perfplat_sqs_encryption_key-${terraform.workspace}"
  target_key_id = aws_kms_replica_key.multi_region_perfplat_sqs_encryption_key_replica.key_id
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
