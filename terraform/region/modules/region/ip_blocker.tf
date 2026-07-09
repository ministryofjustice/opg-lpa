module "ip_blocker" {
  source                       = "./modules/ip_blocker"
  dynamodb_kms_key_arn         = var.dynamodb_kms_key_arn
  lambda_function_aws_iam_role = var.aws_iam_roles.ip_blocker
}
