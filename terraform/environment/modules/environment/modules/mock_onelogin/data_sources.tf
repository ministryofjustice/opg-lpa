data "aws_region" "current" {
  provider = aws.region
}

data "aws_default_tags" "current" {
  provider = aws.region
}
