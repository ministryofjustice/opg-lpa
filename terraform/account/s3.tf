data "aws_elb_service_account" "main" {
  region = "eu-west-1"
}

data "aws_iam_policy_document" "loadbalancer_logging" {
  statement {
    sid = "accessLogBucketAccess"

    resources = [
      "${aws_s3_bucket.access_log.arn}",
      "${aws_s3_bucket.access_log.arn}/*",
    ]

    effect  = "Allow"
    actions = ["s3:PutObject"]

    principals {
      identifiers = [data.aws_elb_service_account.main.id]

      type = "AWS"
    }
  }
}

resource "aws_s3_bucket" "access_log" {
  bucket = "online-lpa-${terraform.workspace}-lb-access-logs"
  acl    = "private"
  tags   = local.default_tags

  server_side_encryption_configuration {
    rule {
      apply_server_side_encryption_by_default {
        sse_algorithm = "aws:kms"
      }
    }
  }
}

resource "aws_s3_bucket_policy" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  policy = data.aws_iam_policy_document.loadbalancer_logging.json
}

resource "aws_s3_bucket" "lpa_pdf_cache" {
  bucket        = lower("online-lpa-pdf-cache-${terraform.workspace}")
  acl           = "private"
  force_destroy = terraform.workspace != "production" ? true : false

  server_side_encryption_configuration {
    rule {
      apply_server_side_encryption_by_default {
        kms_master_key_id = aws_kms_key.lpa_pdf_cache.arn
        sse_algorithm     = "aws:kms"
      }
    }
  }

  # Clear items out teh cache after 1 day.
  lifecycle_rule {
    enabled = true
    expiration {
      days = 1
    }
  }

  tags = local.default_tags
}

resource "aws_s3_bucket_public_access_block" "lpa_pdf_cache" {
  bucket = aws_s3_bucket.lpa_pdf_cache.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_kms_key" "lpa_pdf_cache" {
  description             = "S3 bucket encryption key for lpa_pdf_cache"
  deletion_window_in_days = 7
  tags                    = local.default_tags
}

resource "aws_kms_alias" "lpa_pdf_cache" {
  name          = "alias/lpa_pdf_cache-${terraform.workspace}"
  target_key_id = aws_kms_key.lpa_pdf_cache.key_id
}

resource "aws_s3_bucket" "static_email_assets" {
  count  = terraform.workspace == "production" ? 1 : 0
  bucket = "opg-lpa-email-assets"

  tags   = local.default_tags
  region = "eu-west-1"
}

resource "aws_s3_bucket_object" "govuk_logo" {
  count        = terraform.workspace == "production" ? 1 : 0
  bucket       = aws_s3_bucket.static_email_assets.0.id
  key          = "govuk-logo-v1.png"
  source       = "../../email-assets/govuk-logo-v1.png"
  etag         = filemd5("../../email-assets/govuk-logo-v1.png")
  content_type = "image/png"
}

resource "aws_s3_bucket_object" "opg_logo" {
  count        = terraform.workspace == "production" ? 1 : 0
  bucket       = aws_s3_bucket.static_email_assets.0.id
  key          = "opg-logo-v1.png"
  source       = "../../email-assets/opg-logo-v1.png"
  etag         = filemd5("../../email-assets/opg-logo-v1.png")
  content_type = "image/png"
}

data "aws_iam_policy_document" "static_email_assets_policy" {
  count = terraform.workspace == "production" ? 1 : 0
  statement {
    principals {
      identifiers = ["*"]
      type        = "AWS"
    }
    actions   = ["s3:GetObject"]
    resources = ["${aws_s3_bucket.static_email_assets.0.arn}/*"]
  }
}

resource "aws_s3_bucket_policy" "static_email_assets_policy" {
  count  = terraform.workspace == "production" ? 1 : 0
  bucket = aws_s3_bucket.static_email_assets.0.id
  policy = data.aws_iam_policy_document.static_email_assets_policy.0.json
}
