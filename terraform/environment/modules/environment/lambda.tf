module "jwks_proxy" {
  source      = "./modules/lambda"
  lambda_name = "jwks_proxy"
  environment = var.environment_name
  environment_variables = {
    APP_JWKS_URL = "http://${local.api_service_fqdn}:8080/v2/auth/onelogin/jwks"
  }
  image_uri                   = "${data.aws_ecr_repository.jwks_proxy.repository_url}:latest"
  ecr_arn                     = data.aws_ecr_repository.jwks_proxy.arn
  timeout                     = 900
  lambda_role_policy_document = data.aws_iam_policy_document.jwks_proxy_policy.json
  vpc_subnet_ids              = [for subnet in data.aws_subnet.data : subnet.id]
  vpc_id                      = data.aws_vpc.main.id
}

data "aws_ecr_repository" "jwks_proxy" {
  region   = data.aws_region.current.region
  provider = aws.management
  name     = "online-lpa/jwks_proxy"
}

data "aws_iam_policy_document" "jwks_proxy_policy" {

}

resource "aws_lambda_function_url" "jwks_proxy" {
  function_name      = module.jwks_proxy.lambda_function.function_name
  authorization_type = "NONE"
}
