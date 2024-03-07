
resource "aws_appautoscaling_scheduled_action" "trigger_scale_up" {
  for_each           = var.service_config
  name               = "${var.name}-ecs-scale-up-${each.key}-${terraform.workspace}"
  service_namespace  = each.value.target.service_namespace
  resource_id        = each.value.target.resource_id
  scalable_dimension = each.value.target.scalable_dimension
  schedule           = var.scale_up_time

  scalable_target_action {
    min_capacity = 1
    max_capacity = each.value.scale_up_to
  }
}

resource "aws_appautoscaling_scheduled_action" "trigger_scale_down" {
  for_each           = var.service_config
  name               = "${var.name}-ecs-scale-down-${each.key}-${terraform.workspace}"
  service_namespace  = each.value.target.service_namespace
  resource_id        = each.value.target.resource_id
  scalable_dimension = each.value.target.scalable_dimension
  schedule           = var.scale_down_time

  # AutoScaling actions need to be executed serially
  depends_on = [aws_appautoscaling_scheduled_action.trigger_scale_up]

  scalable_target_action {
    min_capacity = each.value.scale_down_to
    max_capacity = each.value.scale_down_to
  }
}
