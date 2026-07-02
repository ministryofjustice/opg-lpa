resource "aws_kms_key" "lpa_pdf_cache" {
  description             = "S3 bucket encryption key for lpa_pdf_cache"
  deletion_window_in_days = 7
  tags                    = local.pdf_component_tag
  enable_key_rotation     = true
}

resource "aws_kms_alias" "lpa_pdf_cache" {
  name          = "alias/lpa_pdf_cache-${terraform.workspace}"
  target_key_id = aws_kms_key.lpa_pdf_cache.key_id
}

resource "aws_kms_key" "redacted_logs" {
  description             = "S3 bucket encryption key for redacted_logs"
  deletion_window_in_days = 7
  enable_key_rotation     = true
}

resource "aws_kms_alias" "redacted_logs" {
  name          = "alias/redacted_logs-${terraform.workspace}"
  target_key_id = aws_kms_key.redacted_logs.key_id
}
