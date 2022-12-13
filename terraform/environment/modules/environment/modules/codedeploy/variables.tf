variable "name" {
  description = "The name of the application"
  default     = null
}

variable "ecs_cluster_name" {
  description = "The name of the ECS cluster"
}

variable "ecs_service_name" {
  description = "The name of the ECS service"
  default     = null
}

variable "alb_blue_target_group_name" {
  description = "The name of the blue target group"
}

variable "alb_green_target_group_name" {
  description = "The name of the green target group"
}

variable "alb_listener_arn" {
  description = "The ARN of the ALB listener"
}

variable "environment" {
  description = "The environment of the application"
}