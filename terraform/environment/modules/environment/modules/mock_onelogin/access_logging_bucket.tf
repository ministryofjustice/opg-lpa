data "aws_s3_bucket" "access_log" {
  bucket   = "${data.aws_default_tags.current.tags.application}-${data.aws_default_tags.current.tags.account-name}-lb-access-logs-${data.aws_region.current.region}"
  provider = aws.region
}
