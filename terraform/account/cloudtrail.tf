data "aws_caller_identity" "current" {}

resource "aws_cloudtrail" "cloudtrail" {
  name                          = "online_lpa_cloudtrail_${terraform.workspace}"
  s3_bucket_name                = aws_s3_bucket.cloudtrail_logs.id
  s3_key_prefix                 = "prefix"
  include_global_service_events = true
  is_multi_region_trail         = true
  cloud_watch_logs_group_arn    = aws_cloudwatch_log_group.cloudtrail_logs.arn
  cloud_watch_logs_role_arn     = aws_iam_role.cloudtrail_logs.arn
  event_selector {
    read_write_type           = "All"
    include_management_events = true
    data_resource {
      type   = "AWS::S3::Object"
      values = ["arn:aws:s3:::"]
    }
  }
}

resource "aws_cloudwatch_log_group" "cloudtrail_logs" {
  name = "cloudtrail_logs"
}

resource "aws_iam_role" "cloudtrail_logs" {
  name               = "cloudtrail_logs"
  assume_role_policy = data.aws_iam_policy_document.cloudtrail_logs_role_assume_role_policy.json
}

data "aws_iam_policy_document" "cloudtrail_logs_role_assume_role_policy" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["cloudtrail.amazonaws.com"]
    }
  }
}

resource "aws_iam_role_policy" "cloudtrail_logs" {
  name   = "cloudtrail_logs"
  role   = aws_iam_role.cloudtrail_logs.id
  policy = data.aws_iam_policy_document.cloudtrail_logs_role_policy.json
}

data "aws_iam_policy_document" "cloudtrail_logs_role_policy" {
  statement {
    actions = [
      "logs:CreateLogGroup",
      "logs:CreateLogStream",
      "logs:PutLogEvents",
      "logs:DescribeLogGroups",
      "logs:DescribeLogStreams"
    ]

    resources = ["*"]
    effect    = "Allow"
  }
}

resource "aws_kms_key" "cloudtrail_bucket_key" {
  description             = "This key is used to encrypt cloudtrail bucket objects"
  deletion_window_in_days = 10
}

resource "aws_s3_bucket" "cloudtrail_logs" {
  bucket        = "online-lpa-cloudtrail-${terraform.workspace}"
  force_destroy = true
  acl           = "private"
  server_side_encryption_configuration {
    rule {
      apply_server_side_encryption_by_default {
        kms_master_key_id = aws_kms_key.cloudtrail_bucket_key.arn
        sse_algorithm     = "aws:kms"
      }
    }
  }

  policy = data.aws_iam_policy_document.cloudtrail_bucket_policy.json
}

data "aws_iam_policy_document" "cloudtrail_bucket_policy" {
  statement {
    sid       = "AWSCloudTrailAclCheck"
    actions   = ["s3:GetBucketAcl"]
    resources = ["arn:aws:s3:::online-lpa-cloudtrail-${terraform.workspace}"]

    principals {
      type        = "Service"
      identifiers = ["cloudtrail.amazonaws.com"]
    }
  }
  statement {
    sid       = "AWSCloudTrailWrite"
    actions   = ["s3:PutObject"]
    resources = ["arn:aws:s3:::online-lpa-cloudtrail-${terraform.workspace}/prefix/AWSLogs/${data.aws_caller_identity.current.account_id}/*"]

    principals {
      type        = "Service"
      identifiers = ["cloudtrail.amazonaws.com"]
    }
    condition {
      test     = "StringEquals"
      variable = "s3:x-amz-acl"

      values = [
        "bucket-owner-full-control",
      ]
    }

  }
}
