resource "aws_s3_bucket" "static_email_assets" {
  bucket = "opg-lpa-email-assets"

  tags   = local.default_tags
  region = "eu-west-1"
}

resource "aws_s3_bucket_object" "govuk_logo" {
  bucket       = aws_s3_bucket.static_email_assets.id
  key          = "govuk-logo-v1.png"
  source       = "../../email-assets/govuk-logo-v1.png"
  etag         = filemd5("../../email-assets/govuk-logo-v1.png")
  content_type = "image/png"
}

resource "aws_s3_bucket_object" "opg_logo" {
  bucket       = aws_s3_bucket.static_email_assets.id
  key          = "opg-logo-v1.png"
  source       = "../../email-assets/opg-logo-v1.png"
  etag         = filemd5("../../email-assets/opg-logo-v1.png")
  content_type = "image/png"
}

data "aws_iam_policy_document" "static_email_assets_policy" {
  statement {
    principals {
      identifiers = ["*"]
      type        = "AWS"
    }
    actions   = ["s3:GetObject"]
    resources = ["${aws_s3_bucket.static_email_assets.arn}/*"]
  }
}

resource "aws_s3_bucket_policy" "static_email_assets_policy" {
  bucket = aws_s3_bucket.static_email_assets.id
  policy = data.aws_iam_policy_document.static_email_assets_policy.json
}