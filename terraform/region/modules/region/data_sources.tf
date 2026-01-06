data "aws_region" "current" {}

data "aws_caller_identity" "current" {}

data "aws_availability_zones" "default" {}

#tflint-ignore: terraform_unused_declarations
data "aws_default_tags" "current" {}
