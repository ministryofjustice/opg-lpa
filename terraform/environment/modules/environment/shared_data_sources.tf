data "aws_kms_key" "lpa_pdf_sqs" {
  key_id = "alias/mrk_pdf_sqs_encryption_key-${var.account_name}"
}

data "aws_security_group" "front_cache_region" {
  name = "${local.account_name_short}-${var.region_name}-front-cache"
}

data "aws_elasticache_replication_group" "front_cache_region" {
  replication_group_id = "${local.account_name_short}-${var.region_name}-front-cache-rg"
}

data "aws_s3_bucket" "access_log" {
  bucket = "online-lpa-${var.account_name}-${var.region_name}-lb-access-logs"
}

data "aws_s3_bucket" "lpa_pdf_cache" {
  bucket = lower("online-lpa-pdf-cache-${var.account_name}-${var.region_name}")
}

data "aws_kms_key" "lpa_pdf_cache" {
  key_id = "alias/lpa_pdf_cache-${var.account_name}"
}

data "aws_kms_key" "cloudwatch_encryption" {
  key_id = "alias/cloudwatch_encryption-${var.account_name}"
}

data "aws_acm_certificate" "certificate_front" {
  domain = "${local.cert_prefix_internal}${local.dns_namespace_dev_prefix}front.lpa.opg.service.justice.gov.uk"
}

data "aws_acm_certificate" "certificate_admin" {
  domain = "${local.cert_prefix_internal}${local.dns_namespace_dev_prefix}admin.lpa.opg.service.justice.gov.uk"
}

data "aws_acm_certificate" "public_facing_certificate" {
  domain = "${local.cert_prefix_public_facing}${local.dns_namespace_dev_prefix}lastingpowerofattorney.service.gov.uk"
}

data "aws_iam_role" "ecs_autoscaling_service_role" {
  name = "AWSServiceRoleForApplicationAutoScaling_ECSService"
}

data "aws_kms_alias" "secrets_encryption_alias" {
  name = "alias/secrets_encryption_key-${var.account_name}"
}

data "aws_kms_alias" "multi_region_secrets_encryption_alias" {
  name = "alias/mrk_secrets_encryption_key-${var.account_name}"
}

data "aws_region" "current" {}

data "aws_caller_identity" "current" {}
