data "aws_s3_bucket" "lastingpowerofattorney_service_gov_uk" {
  bucket   = "lastingpowerofattorney.service.gov.uk"
  provider = aws.eu_central_1
}

output "s3_bucket" {
  value = data.aws_s3_bucket.lastingpowerofattorney_service_gov_uk
}


locals {
  s3_origin_id = "S3-lastingpowerofattorney.service.gov.uk"
}
# https://www.terraform.io/docs/providers/aws/r/cloudfront_origin_access_identity.html

resource "aws_cloudfront_distribution" "maintenance" {
  origin {
    domain_name = data.aws_s3_bucket.lastingpowerofattorney_service_gov_uk.bucket_domain_name
    origin_id   = local.s3_origin_id
  }

  enabled             = true
  is_ipv6_enabled     = false
  http_version        = "http1.1"
  comment             = "Managed by opg-lpa/terraform/terraform_maintenance"
  default_root_object = "index.html"

  aliases = [
    "lastingpowerofattorney.service.gov.uk",
    "lastingpowerofattorney.service.gov.uk.s3-website.eu-central-1.amazonaws.com",
    "www.lastingpowerofattorney.service.gov.uk",
    "maintenance.lastingpowerofattorney.service.gov.uk",
  ]
  custom_error_response {
    error_caching_min_ttl = 300
    error_code            = 403
    response_code         = 200
    response_page_path    = "/index.html"
  }
  custom_error_response {
    error_caching_min_ttl = 300
    error_code            = 404
    response_code         = 200
    response_page_path    = "/index.html"
  }

  default_cache_behavior {
    allowed_methods  = ["GET", "HEAD", "OPTIONS"]
    cached_methods   = ["GET", "HEAD", "OPTIONS"]
    target_origin_id = local.s3_origin_id

    forwarded_values {
      query_string = false

      cookies {
        forward = "none"
      }
    }

    viewer_protocol_policy = "redirect-to-https"
    min_ttl                = 0
    default_ttl            = 600
    max_ttl                = 600
  }

  price_class = "PriceClass_100"

  tags = local.default_tags

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    acm_certificate_arn            = aws_acm_certificate.maintenance_cloudfront.arn
    cloudfront_default_certificate = false
    minimum_protocol_version       = "TLSv1.1_2016"
    ssl_support_method             = "sni-only"
  }
}
