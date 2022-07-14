data "aws_kms_key" "access_log_key" {
  key_id = "alias/mrk_access_logs_lb_encryption_key-${terraform.workspace}"
}

data "aws_elb_service_account" "main" {
  region = local.region_name
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

  statement {
    sid    = "AllowSSLRequestsOnly"
    effect = "Deny"

    resources = [
      aws_s3_bucket.access_log.arn,
      "${aws_s3_bucket.access_log.arn}/*",
    ]

    actions = ["s3:*"]

    condition {
      test     = "Bool"
      variable = "aws:SecureTransport"
      values   = ["false"]
    }

    principals {
      type        = "*"
      identifiers = ["*"]
    }
  }
}

#versioning not required for a logging bucket bucket logging not needed. 
#encryption of ALB access logs not supported with CMK
#tfsec:ignore:AWS002  #tfsec:ignore:AWS077 #tfsec:ignore:aws-s3-encryption-customer-key
resource "aws_s3_bucket" "access_log" {
  bucket = "online-lpa-${local.account_name}-${local.region_name}-lb-access-logs"
  acl    = "private"

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
  bucket        = lower("online-lpa-pdf-cache-${local.account_name}-${local.region_name}")
  acl           = "private"
  force_destroy = local.account_name != "production" ? true : false

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

}

resource "aws_s3_bucket_policy" "lpa_pdf_cache" {
  bucket = aws_s3_bucket.lpa_pdf_cache.id
  policy = data.aws_iam_policy_document.lpa_pdf_cache_policy.json

}

resource "aws_s3_bucket_public_access_block" "lpa_pdf_cache" {
  bucket = aws_s3_bucket.lpa_pdf_cache.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

data "aws_iam_policy_document" "lpa_pdf_cache_policy" {
  statement {
    sid    = "AllowSSLRequestsOnly"
    effect = "Deny"

    resources = [
      aws_s3_bucket.lpa_pdf_cache.arn,
      "${aws_s3_bucket.lpa_pdf_cache.arn}/*",
    ]

    actions = ["s3:*"]

    condition {
      test     = "Bool"
      variable = "aws:SecureTransport"
      values   = ["false"]
    }

    principals {
      type        = "*"
      identifiers = ["*"]
    }
  }
}
