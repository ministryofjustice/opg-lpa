
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
#tfsec:ignore:aws-s3-enable-bucket-logging  #tfsec:ignore:aws-s3-enable-versioning
resource "aws_s3_bucket" "access_log" {
  bucket = "online-lpa-${local.account_name}-${local.region_name}-lb-access-logs"
  acl    = "private"
}

#tfsec:ignore:aws-s3-encryption-customer-key
#encryption of ALB access logs not supported with CMK
resource "aws_s3_bucket_server_side_encryption_configuration" "access_log" {
  bucket = aws_s3_bucket.access_log.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "aws:kms"
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

#tfsec:ignore:aws-s3-enable-bucket-logging #tfsec:ignore:aws-s3-enable-versioning - no logging or versioning required as a temp cache
resource "aws_s3_bucket" "lpa_pdf_cache" {
  bucket        = lower("online-lpa-pdf-cache-${local.account_name}-${local.region_name}")
  acl           = "private"
  force_destroy = local.account_name != "production" ? true : false
}

resource "aws_s3_bucket_lifecycle_configuration" "lpa_pdf_cache" {
  bucket = aws_s3_bucket.lpa_pdf_cache.id
  rule {
    id     = "expiremessages"
    status = "Enabled"
    expiration {
      days = 1
    }
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "lpa_pdf_cache" {
  bucket = aws_s3_bucket.lpa_pdf_cache.id

  rule {
    apply_server_side_encryption_by_default {
      kms_master_key_id = aws_kms_key.lpa_pdf_cache.arn
      sse_algorithm     = "aws:kms"
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

# A bucket to hold redacted logs until they expired

resource "aws_s3_bucket" "redacted_logs" {
  bucket = "redacted-logs.${local.account_name}.${local.region_name}.lpa.opg.justice.gov.uk"
}

resource "aws_s3_bucket_lifecycle_configuration" "redacted_logs" {
  bucket = aws_s3_bucket.redacted_logs.bucket
  rule {
    id     = "expirelogs"
    status = "Enabled"

    expiration {
      days = 120
    }


  }
}

resource "aws_s3_bucket_acl" "redacted_logs" {
  bucket = aws_s3_bucket.redacted_logs.id
  acl    = "private"
}

resource "aws_s3_bucket_server_side_encryption_configuration" "redacted_logs" {
  bucket = aws_s3_bucket.redacted_logs.id

  rule {
    apply_server_side_encryption_by_default {
      kms_master_key_id = aws_kms_key.redacted_logs.arn
      sse_algorithm     = "aws:kms"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "redacted_logs" {
  bucket                  = aws_s3_bucket.redacted_logs.id
  block_public_acls       = true
  block_public_policy     = true
  restrict_public_buckets = true
  ignore_public_acls      = true
}
