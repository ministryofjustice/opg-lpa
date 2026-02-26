data "aws_caller_identity" "current" {}

data "aws_caller_identity" "backup" {
  provider = aws.backup
}
