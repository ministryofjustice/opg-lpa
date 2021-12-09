
data "aws_ecr_repository" "lpa_api_mock_gateway" {
  provider = aws.management
  name     = "online-lpa/mock_gateway"
}

data "aws_ecr_repository" "lpa_api_mock_sirius" {
  provider = aws.management
  name     = "online-lpa/mock_sirius"
}

//-----------------------------------------------
// mock sirius ECS Service Task Container level config

locals {
  mock_gateway = jsonencode(
    {
      "cpu" : 1,
      "essential" : true,
      "image" : "${data.aws_ecr_repository.lpa_api_mock_gateway.repository_url}:${var.container_version}",
      "mountPoints" : [],
      "name" : "gateway",
      "portMappings" : [
        {
          "containerPort" : 5010,
          "hostPort" : 5010,
          "protocol" : "tcp"
        }
      ],
      "volumesFrom" : [],
      "logConfiguration" : {
        "logDriver" : "awslogs",
        "options" : {
          "awslogs-group" : aws_cloudwatch_log_group.application_logs.name,
          "awslogs-region" : "${local.region_name}",
          "awslogs-stream-prefix" : "${local.environment}.api-web.online-lpa"
        }
      }
      "environment" : [
        { "name" : "OPG_LPA_STATUS_ENDPOINT", "value" : local.account.sirius_api_gateway_endpoint }
      ]
    }
  )

  mock_sirius = jsonencode(
    {
      "cpu" : 1,
      "essential" : true,
      "image" : "${data.aws_ecr_repository.lpa_api_mock_gateway.repository_url}:${var.container_version}",
      "mountPoints" : [],
      "name" : "mocksirius",
      "portMappings" : [
        {
          "containerPort" : 5011,
          "hostPort" : 5011,
          "protocol" : "tcp"
        }
      ],
      "volumesFrom" : [],
      "logConfiguration" : {
        "logDriver" : "awslogs",
        "options" : {
          "awslogs-group" : aws_cloudwatch_log_group.application_logs.name,
          "awslogs-region" : "${local.region_name}",
          "awslogs-stream-prefix" : "${local.environment}.api-web.online-lpa"
        }
      }
    }
  )

}
