resource "aws_s3_bucket" "lastingpowerofattorney_service_gov_uk" {
  bucket        = "lastingpowerofattorney.service.gov.uk"
  region        = "eu-central-1"
  request_payer = "BucketOwner"
  acl           = "public-read"
  website {
    error_document = "index.html"
    index_document = "index.html"
  }

  tags     = local.default_tags
  provider = aws.eu_central_1
}

resource "aws_s3_bucket_policy" "lastingpowerofattorney_service_gov_uk" {
  bucket = aws_s3_bucket.lastingpowerofattorney_service_gov_uk.id
  policy = data.aws_iam_policy_document.lastingpowerofattorney_service_gov_uk.json
}

data "aws_iam_policy_document" "lastingpowerofattorney_service_gov_uk" {
  statement {
    sid = "PublicReadGetObject"

    actions = ["s3:GetObject"]
    principals {
      type        = "*"
      identifiers = ["*"]
    }

    resources = ["arn:aws:s3:::lastingpowerofattorney.service.gov.uk/*"]
  }
}
