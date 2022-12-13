locals {
  service_name = var.ecs_service_name == null ? var.name : var.ecs_service_name
}

resource "aws_codedeploy_app" "main" {
  name             = "${var.environment}-${var.name}"
  compute_platform = "ECS"
}

resource "aws_codedeploy_deployment_group" "main" {
  deployment_group_name = "${var.environment}-${aws_codedeploy_app.main.name}"

  app_name               = aws_codedeploy_app.main.name
  service_role_arn       = aws_iam_role.main.arn
  deployment_config_name = "CodeDeployDefault.ECSAllAtOnce"

  auto_rollback_configuration {
    enabled = true
    events  = ["DEPLOYMENT_FAILURE"]
  }

  deployment_style {
    deployment_option = "WITH_TRAFFIC_CONTROL"
    deployment_type   = "BLUE_GREEN"
  }

  blue_green_deployment_config {
    deployment_ready_option {
      action_on_timeout = "CONTINUE_DEPLOYMENT"
    }

    terminate_blue_instances_on_deployment_success {
      action                           = "TERMINATE"
      termination_wait_time_in_minutes = 5
    }
  }

  dynamic "load_balancer_info" {
    for_each = var.alb_listener_arn == null ? [] : var.alb_listener_arn

    content {
      target_group_pair_info {
        prod_traffic_route {
          listener_arns = ["${var.alb_listener_arn}"]
        }

        target_group {
          name = var.alb_blue_target_group_name
        }

        target_group {
          name = var.alb_green_target_group_name
        }
      }
    }
  }

  ecs_service {
    cluster_name = var.ecs_cluster_name
    service_name = local.service_name
  }

}

resource "aws_iam_role" "main" {
  name               = "example"
  assume_role_policy = data.aws_iam_policy_document.assume_role_policy.json
}

data "aws_iam_policy_document" "assume_role_policy" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["codedeploy.amazonaws.com"]
    }
  }
}

resource "aws_iam_role_policy_attachment" "main" {
  role       = aws_iam_role.main.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSCodeDeployRoleForECSLimited"
}

