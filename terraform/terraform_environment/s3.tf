resource "aws_s3_bucket" "static_email_assets" {
  bucket = "opg-lpa-email-assets"

  tags = local.default_tags
  region = "eu-west-1"
  
}