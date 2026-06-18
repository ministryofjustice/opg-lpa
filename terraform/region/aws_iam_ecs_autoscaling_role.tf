resource "aws_iam_service_linked_role" "ecs_autoscaling_service_role" {
  aws_service_name = "ecs.application-autoscaling.amazonaws.com"
}
