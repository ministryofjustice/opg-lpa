data "aws_region" "current" {}

data "aws_vpc_endpoint" "secrets_manager" {
  service_name = "com.amazonaws.${data.aws_region.current.region}.secretsmanager"
  vpc_id       = var.vpc_id
}
