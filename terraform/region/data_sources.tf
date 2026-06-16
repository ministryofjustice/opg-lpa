data "aws_caller_identity" "current" {}

data "aws_caller_identity" "backup" {
  provider = aws.backup
}

data "aws_region" "current" {}

data "aws_region" "eu_west_2" {
  provider = aws.eu-west-2
}
