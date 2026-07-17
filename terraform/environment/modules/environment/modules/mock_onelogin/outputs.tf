output "load_balancer" {
  value = aws_lb.mock_onelogin
}

output "load_balancer_security_group" {
  value = aws_security_group.mock_onelogin_loadbalancer
}

output "ecs_service" {
  value = aws_ecs_service.mock_onelogin
}
