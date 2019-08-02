resource "aws_s3_bucket" "lpa_pdf_cache" {
  bucket        = lower("online-lpa-pdf-cache-${local.environment}")
  acl           = "private"
  force_destroy = local.environment != "production" ? true : false

  # server_side_encryption_configuration {
  #   rule {
  #     apply_server_side_encryption_by_default {
  #       kms_master_key_id = aws_kms_key.lpa_pdf_cache.arn
  #       sse_algorithm     = "aws:kms"
  #     }
  #   }
  # }

  tags = local.default_tags
}

resource "aws_kms_key" "lpa_pdf_cache" {
  description             = "S3 bucket encryption key for lpa_pdf_cache"
  deletion_window_in_days = 7
  tags                    = local.default_tags
}

resource "aws_kms_alias" "lpa_pdf_cache" {
  name          = "alias/lpa_pdf_cache-${local.environment}"
  target_key_id = aws_kms_key.lpa_pdf_cache.key_id
}

