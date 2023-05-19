resource "aws_ecs_cluster" "online-lpa" {
  name = "${var.environment_name}-online-lpa"
  tags = local.shared_component_tag

  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  depends_on = [aws_iam_role_policy.execution_role]
}

resource "aws_ecs_cluster_capacity_providers" "online-lpa" {
  cluster_name = aws_ecs_cluster.online-lpa.name

  capacity_providers = ["FARGATE", "FARGATE_SPOT"]

  default_capacity_provider_strategy {
    base              = 1
    weight            = 100
    capacity_provider = "FARGATE"
  }
}


data "aws_cloudwatch_log_group" "online-lpa" {
  name = "online-lpa"
  tags = local.shared_component_tag
}
