locals {

  account_name_short = var.account.account_name_short

  cert_prefix_public_facing   = var.environment_name == "production" ? "www." : "*."
  cert_prefix_internal        = var.account_name == "production" ? "" : "*."
  dns_namespace_env           = var.environment_name == "production" ? "" : "${var.environment_name}."
  dns_namespace_dev_prefix    = var.account_name == "development" ? "development." : ""
  track_from_date             = "2019-04-01"
  front_dns                   = "front.lpa"
  admin_dns                   = "admin.lpa"
  pager_duty_ops_service_name = "Make a Lasting Power of Attorney Ops Monitoring"
  region_name                 = var.account.regions[data.aws_region.current.region].region

  shared_component_tag = {
    component = "shared"
  }

  admin_component_tag = {
    component = "admin"
  }

  front_component_tag = {
    component = "front"
  }

  api_component_tag = {
    component = "api"
  }

  dynamodb_component_tag = {
    component = "dynamodb"
  }

  db_component_tag = {
    component = "db"
  }

  pdf_component_tag = {
    component = "pdf"
  }

  seeding_component_tag = {
    component = "seeding"
  }

  aws_otel_collector = jsonencode(
    {
      cpu         = 0,
      essential   = true,
      image       = "311462405659.dkr.ecr.eu-west-1.amazonaws.com/aws-otel-collector-public-ecr/aws-observability/aws-otel-collector:v0.42.0",
      mountPoints = [],
      name        = "aws-otel-collector",
      command = [
        "--config=/etc/ecs/ecs-default-config.yaml"
      ],
      healthCheck = {
        command     = ["CMD", "/healthcheck"],
        startPeriod = 5,
        interval    = 10,
        timeout     = 5,
        retries     = 3
      },
      volumesFrom = [],
      logConfiguration = {
        logDriver = "awslogs",
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
          awslogs-region        = var.region_name,
          awslogs-stream-prefix = "${var.environment_name}.otel.online-lpa"
        }
      }
    }
  )

}
