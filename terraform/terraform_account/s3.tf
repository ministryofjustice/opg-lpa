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
