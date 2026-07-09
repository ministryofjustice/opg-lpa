module "ip_blocker" {
  source               = "./modules/ip_blocker"
  dynamodb_kms_key_arn = var.dynamodb_kms_key_arn
}
