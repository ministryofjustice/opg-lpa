//------------------------------------------------
// Trigger times

resource "aws_cloudwatch_event_rule" "middle_of_the_night" {
  name                = "${var.environment_name}-middle-of-the-night-cron"
  schedule_expression = "cron(0 3 * * ? *)" // 3am UTC, every day.
  tags                = local.api_component_tag
}

resource "aws_cloudwatch_event_rule" "mid_morning" {
  name                = "${var.environment_name}-mid-morning-cron"
  schedule_expression = "cron(0 10 * * ? *)" // 10am UTC, every day.
  tags                = local.api_component_tag
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
    task_definition_arn = aws_ecs_task_definition.api.arn
    launch_type         = "FARGATE"
    platform_version    = "1.4.0"

    network_configuration {
      security_groups = [
        aws_security_group.api_ecs_service.id,
        aws_security_group.rds-client.id,
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
          command = ["php", "/app/vendor/bin/laminas", "service-api:account-cleanup"]
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
    task_definition_arn = aws_ecs_task_definition.api.arn
    launch_type         = "FARGATE"
    platform_version    = "1.4.0"

    network_configuration {
      security_groups = [
        aws_security_group.api_ecs_service.id,
        aws_security_group.rds-client.id,
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
          command = ["php", "/app/vendor/bin/laminas", "service-api:generate-stats"]
        }
      ]
  })
}
