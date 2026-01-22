resource "aws_ecs_task_definition" "api_cron" {
  family                   = "${terraform.workspace}-api-cron"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  container_definitions    = "[${local.api_app}, ${local.aws_otel_collector}]"
  task_role_arn            = var.ecs_iam_task_roles.api.arn
  execution_role_arn       = var.ecs_execution_role.arn
  tags                     = local.api_component_tag
  volume {
    name = "app_tmp"
  }
}

//------------------------------------------------
// Trigger times

resource "aws_cloudwatch_event_rule" "middle_of_the_night" {
  name                = "${var.environment_name}-middle-of-the-night-cron"
  description         = "3am UTC, every day. Used for Generate Stats"
  schedule_expression = "cron(10 13 * * ? *)"
  # schedule_expression = "cron(0 3 * * ? *)"
  tags = local.api_component_tag
}

resource "aws_cloudwatch_event_rule" "mid_morning" {
  name                = "${var.environment_name}-mid-morning-cron"
  description         = "10am UTC, every day. Used for Account Cleanup"
  schedule_expression = "cron(15 13 * * ? *)"
  # schedule_expression = "cron(0 10 * * ? *)"
  tags = local.api_component_tag
}

//------------------------------------------------
// Account Cleanup Task

resource "aws_cloudwatch_event_target" "api_ecs_cron_event_account_cleanup" {
  count = var.account_name == "preproduction" ? 0 : 1
  # don't run crons on preprod

  target_id = "account-cleanup"
  arn       = aws_ecs_cluster.online-lpa.arn
  rule      = aws_cloudwatch_event_rule.mid_morning.name
  role_arn  = var.ecs_iam_task_roles.cloudwatch_events.arn

  ecs_target {
    task_count          = 1
    task_definition_arn = aws_ecs_task_definition.api_cron.arn
    launch_type         = "FARGATE"
    platform_version    = "1.4.0"

    network_configuration {
      security_groups = [
        aws_security_group.api_ecs_service.id,
        local.rds_client_sg_id,
      ]
      subnets          = local.app_subnet_ids
      assign_public_ip = false
    }
  }

  input = jsonencode(
    {
      containerOverrides = [
        {
          name    = "app",
          command = ["php", "/app/vendor/bin/laminas", "service-api:account-cleanup"],
          logConfiguration = {
            logDriver = "awslogs",
            options = {
              awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
              awslogs-region        = var.region_name,
              awslogs-stream-prefix = "${var.environment_name}.account-cleanup.online-lpa",
            }
          }
        }
      ]
  })

}

//------------------------------------------------
// Generate Stats Task

resource "aws_cloudwatch_event_target" "api_ecs_cron_event_generate_stats" {
  target_id = "generate-stats"
  arn       = aws_ecs_cluster.online-lpa.arn
  rule      = aws_cloudwatch_event_rule.middle_of_the_night.name
  role_arn  = var.ecs_iam_task_roles.cloudwatch_events.arn

  ecs_target {
    task_count          = 1
    task_definition_arn = aws_ecs_task_definition.api_cron.arn
    launch_type         = "FARGATE"
    platform_version    = "1.4.0"

    network_configuration {
      security_groups = [
        aws_security_group.api_ecs_service.id,
        local.rds_client_sg_id,
      ]
      subnets          = local.app_subnet_ids
      assign_public_ip = false
    }
  }

  input = jsonencode(
    {
      containerOverrides = [
        {
          name    = "app",
          command = ["php", "/app/vendor/bin/laminas", "service-api:generate-stats"],
          logConfiguration = {
            options = {
              awslogs-group         = aws_cloudwatch_log_group.application_logs.name,
              awslogs-region        = var.region_name,
              awslogs-stream-prefix = "${var.environment_name}.generate-stats.online-lpa",
            }
          }
        }
      ]
  })
}
