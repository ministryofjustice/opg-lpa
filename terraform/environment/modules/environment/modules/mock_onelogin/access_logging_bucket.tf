data "aws_s3_bucket" "access_log" {
  bucket   = "online-lpa-${var.account_name}-${data.aws_region.current.region}-lb-access-logs"
  provider = aws.region
}
