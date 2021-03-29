data "aws_iam_role" "ecs_autoscaling_service_role" {
  name = "AWSServiceRoleForApplicationAutoScaling_ECSService"
}


resource "aws_appautoscaling_target" "ecs_service_scheduled" {
  for_each           = var.service_config
  min_capacity       = 1
  max_capacity       = each.value.scale_up_to
  resource_id        = "service/${var.ecs_cluster_name}/${each.key}"
  role_arn           = data.aws_iam_role.ecs_autoscaling_service_role.arn
  scalable_dimension = "ecs:service:DesiredCount"
  service_namespace  = "ecs"
}

resource "aws_appautoscaling_scheduled_action" "trigger_scale_up" {
  for_each           = var.service_config
  name               = "${var.name}-ecs-scale-up-${each.key}-${terraform.workspace}"
  service_namespace  = aws_appautoscaling_target.ecs_service_scheduled[each.key].service_namespace
  resource_id        = aws_appautoscaling_target.ecs_service_scheduled[each.key].resource_id
  scalable_dimension = aws_appautoscaling_target.ecs_service_scheduled[each.key].scalable_dimension
  schedule           = var.scale_up_time

  scalable_target_action {
    min_capacity = 1
    max_capacity = each.value.scale_up_to
  }
}

resource "aws_appautoscaling_scheduled_action" "trigger_scale_down" {
  for_each           = var.service_config
  name               = "${var.name}-ecs-scale-down-${each.key}-${terraform.workspace}"
  service_namespace  = aws_appautoscaling_target.ecs_service_scheduled[each.key].service_namespace
  resource_id        = aws_appautoscaling_target.ecs_service_scheduled[each.key].resource_id
  scalable_dimension = aws_appautoscaling_target.ecs_service_scheduled[each.key].scalable_dimension
  schedule           = var.scale_down_time

  scalable_target_action {
    min_capacity = each.value.scale_down_to
    max_capacity = each.value.scale_down_to
  }
}
