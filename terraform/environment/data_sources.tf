data "aws_caller_identity" "current" {}

data "aws_iam_policy" "default_boundary" {
  name = "opg-lpa-non-ci-boundary"
}
