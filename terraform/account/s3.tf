data "aws_elb_service_account" "main" {
  region = "eu-west-1"
}

data "aws_iam_policy_document" "loadbalancer_logging" {
  statement {
    sid = "accessLogBucketAccess"

    resources = [
      aws_s3_bucket.access_log.arn,
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

#versioning not required for a logging bucket bucket logging not needed
#tfsec:ignore:AWS002  #tfsec:ignore:AWS077
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

resource "aws_s3_bucket_public_access_block" "access_log" {
  bucket                  = aws_s3_bucket.access_log.id
  block_public_acls       = true
  block_public_policy     = true
  restrict_public_buckets = true
  ignore_public_acls      = true
}

resource "aws_s3_bucket_policy" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  policy = data.aws_iam_policy_document.loadbalancer_logging.json

}

#tfsec:ignore:AWS002 #tfsec:ignore:AWS077 - no logging or versioning required as a temp cache
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
  tags                    = merge(local.default_tags, local.pdf_component_tag)
  enable_key_rotation     = true
}

resource "aws_kms_alias" "lpa_pdf_cache" {
  name          = "alias/lpa_pdf_cache-${terraform.workspace}"
  target_key_id = aws_kms_key.lpa_pdf_cache.key_id
}
