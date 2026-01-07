resource "aws_lb_target_group" "admin" {
  name                 = "${var.environment_name}-admin"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = local.vpc_id
  deregistration_delay = 0
  health_check {
    enabled             = true
    interval            = 30
    path                = "/robots.txt"
    healthy_threshold   = 3
    unhealthy_threshold = 3
    matcher             = 200
  }
  depends_on = [aws_lb.admin]
  tags       = local.admin_component_tag
}

resource "aws_lb" "admin" {
  name = "${var.environment_name}-admin"
  #tfsec:ignore:aws-elb-alb-not-public - public facing load balancer
  internal                   = false
  load_balancer_type         = "application"
  subnets                    = local.lb_subnet_ids
  tags                       = local.admin_component_tag
  drop_invalid_header_fields = true
  security_groups = [
    aws_security_group.admin_loadbalancer.id,
  ]
  enable_deletion_protection = var.account_name == "development" ? false : true
  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "${var.environment_name}-admin"
    enabled = true
  }
}

resource "aws_lb_listener" "admin_loadbalancer" {
  load_balancer_arn = aws_lb.admin.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-FS-1-2-Res-2020-10"

  certificate_arn = data.aws_acm_certificate.certificate_admin.arn

  default_action {
    target_group_arn = aws_lb_target_group.admin.arn
    type             = "forward"
  }
}

#tfsec:ignore:aws-ec2-add-description-to-security-group - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "admin_loadbalancer" {
  name        = "${var.environment_name}-admin-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = local.vpc_id
  tags        = local.admin_component_tag
}

resource "aws_security_group_rule" "admin_loadbalancer_ingress" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.allowed_ip_list.moj_sites
  security_group_id = aws_security_group.admin_loadbalancer.id
  description       = "MoJ Sites to Admin ELB - HTTPS"
}

resource "aws_security_group_rule" "admin_loadbalancer_egress" {
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:aws-ec2-no-public-egress-sgr - public facing load balancer
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.admin_loadbalancer.id
  description       = "Admin Loadbalancer to Anywhere - All Traffic"
}
