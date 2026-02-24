data "aws_caller_identity" "current" {}

data "aws_kms_key" "aurora_new_key" {
  key_id = "alias/opg-lpa-${local.account_name}-rds-encryption-key"
}

data "aws_kms_key" "aurora_default_key" {
  key_id = "alias/aws/rds"
}
