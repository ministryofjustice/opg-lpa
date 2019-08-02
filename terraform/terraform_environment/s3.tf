resource "aws_s3_bucket" "lpa-pdf-cache" {
  bucket        = "lpa-pdf-cache-${local.environment}"
  acl           = "private"
  force_destroy = local.environment != "production" ? true : false

  # server_side_encryption_configuration {
  #   rule {
  #     apply_server_side_encryption_by_default {
  #       kms_master_key_id = aws_kms_key.lpa-pdf-cache.arn
  #       sse_algorithm     = "aws:kms"
  #     }
  #   }
  # }

  tags = local.default_tags
}

resource "aws_kms_key" "lpa-pdf-cache" {
  description             = "S3 bucket encryption key for lpa-pdf-cache"
  deletion_window_in_days = 7
  tags                    = local.default_tags
}

resource "aws_kms_alias" "lpa-pdf-cache" {
  name          = "alias/lpa-pdf-cache-${local.environment}"
  target_key_id = aws_kms_key.lpa-pdf-cache.key_id
}

