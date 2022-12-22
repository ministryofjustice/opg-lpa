resource "aws_ecs_cluster" "online-lpa" {
  name = "${var.environment_name}-online-lpa"
  tags = local.shared_component_tag

  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  depends_on = [aws_iam_role_policy.execution_role]
}

data "aws_cloudwatch_log_group" "online-lpa" {
  name = "online-lpa"
  tags = local.shared_component_tag
}