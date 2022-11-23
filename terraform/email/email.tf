data "aws_route53_zone" "opg_service_justice_gov_uk" {
  provider = aws.management
  name     = "opg.service.justice.gov.uk"
}

resource "aws_ses_domain_identity" "casper" {
  domain = "lpa.${data.aws_route53_zone.opg_service_justice_gov_uk.name}"
}
locals {
  receiver_address = "caspertests@${aws_ses_domain_identity.casper.domain}"
}
resource "aws_route53_record" "casper_amazonses_verification_record" {
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.id
  name     = "_amazonses.${aws_ses_domain_identity.casper.id}"
  type     = "TXT"
  ttl      = "600"
  records  = [aws_ses_domain_identity.casper.verification_token]
  provider = aws.management
}

resource "aws_ses_domain_identity_verification" "casper_verification" {
  domain     = aws_ses_domain_identity.casper.id
  depends_on = [aws_route53_record.casper_amazonses_verification_record]
}

resource "aws_route53_record" "casper_amazonses_mx" {
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.id
  name     = aws_ses_domain_identity.casper.domain
  type     = "MX"
  ttl      = "600"
  records  = ["10 inbound-smtp.eu-west-1.amazonaws.com"]
  provider = aws.management
}

# Create S3 bucket for SES mail storage
# this is ony used for emil testing, which will change anyway
#tfsec:ignore:aws-s3-enable-bucket-encryption #tfsec:ignore:aws-s3-enable-bucket-logging #tfsec:ignore:aws-s3-enable-versioning #tfsec:ignore:aws-s3-encryption-customer-key
resource "aws_s3_bucket" "mailbox" {
  bucket = "opg-lpa-casper-mailbox"
  acl    = "private"
}

resource "aws_s3_bucket_lifecycle_configuration" "mailbox" {
  bucket = aws_s3_bucket.mailbox.id
  rule {
    id     = "expiremessages"
    status = "Enabled"
    expiration {
      days = 1
    }
  }
}

resource "aws_s3_bucket_public_access_block" "mailbox" {
  bucket                  = aws_s3_bucket.mailbox.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_policy" "mailbox" {
  bucket = aws_s3_bucket.mailbox.id
  policy = data.aws_iam_policy_document.mailbox.json
}

data "aws_iam_policy_document" "mailbox" {
  statement {
    sid    = "AllowSESPuts"
    effect = "Allow"
    principals {
      type        = "Service"
      identifiers = ["ses.amazonaws.com"]
    }
    actions = [
      "s3:PutObject"
    ]
    resources = ["${aws_s3_bucket.mailbox.arn}/*"]
    condition {
      test     = "StringEquals"
      variable = "aws:Referer"
      values = [
        local.account.account_id,
      ]
    }
  }
}

# Create a recipient rule set
resource "aws_ses_receipt_rule_set" "main" {
  rule_set_name = "s3"
}

resource "aws_ses_receipt_rule" "main" {
  name          = "s3"
  rule_set_name = aws_ses_receipt_rule_set.main.rule_set_name
  recipients    = [local.receiver_address]
  enabled       = true
  scan_enabled  = true
  s3_action {
    bucket_name       = aws_s3_bucket.mailbox.id
    object_key_prefix = "mailbox/${local.receiver_address}"
    position          = 1
  }
}

# Activate recipient rule set
resource "aws_ses_active_receipt_rule_set" "main" {
  rule_set_name = aws_ses_receipt_rule_set.main.rule_set_name
}
