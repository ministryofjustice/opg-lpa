
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
          "containerPort" : 5000,
          "hostPort" : 5000,
          "protocol" : "tcp"
        }
      ],
      "volumesFrom" : [],
      "logConfiguration" : {
        "logDriver" : "awslogs",
        "options" : {
          "awslogs-group" : aws_cloudwatch_log_group.application_logs.name,
          "awslogs-region" : "${var.region_name}",
          "awslogs-stream-prefix" : "${var.environment_name}.mock-gateway.online-lpa"
        }
      }
      "environment" : [
        { "name" : "OPG_LPA_STATUS_ENDPOINT", "value" : var.account.sirius_api_gateway_endpoint },
        { "name" : "OPG_LPA_MOCK_SIRIUS_ADDRESS", "value" : "http://localhost:5011" }
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
          "awslogs-region" : "${var.region_name}",
          "awslogs-stream-prefix" : "${var.environment_name}.mock-sirius.online-lpa"
        }
      }
    }
  )

}
