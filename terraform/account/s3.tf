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

# We will keep this in for historical purposes. we need to think how far back we need this.
#versioning not required for a logging bucket bucket logging not needed
#tfsec:ignore:aws-s3-enable-bucket-logging  #tfsec:ignore:aws-s3-enable-versioning
resource "aws_s3_bucket" "access_log" {
  bucket = "online-lpa-${terraform.workspace}-lb-access-logs"
}

resource "aws_s3_bucket_acl" "access_log" {
  bucket = aws_s3_bucket.access_log.id
  acl    = "private"
}

#encryption of ALB access logs not supported with CMK
#tfsec:ignore:aws-s3-encryption-customer-key
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
