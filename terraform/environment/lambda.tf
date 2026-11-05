module "jwks_proxy" {
  source      = "./modules/lambda"
  lambda_name = "jwks_proxy"
  environment = local.environment_name
  environment_variables = {

  }
  image_uri = "${data.aws_ecr_repository.jwks_proxy.repository_url}:${var.container_version}"
  ecr_arn   = data.aws_ecr_repository.jwks_proxy.arn
  timeout   = 900
}

data "aws_ecr_repository" "jwks_proxy" {
  provider = aws.management
  name     = "online-lpa/jwks_proxy"
}
