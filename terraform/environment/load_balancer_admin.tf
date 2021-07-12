resource "aws_lb_target_group" "admin" {
  name                 = "${local.environment}-admin"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_vpc.default.id
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
  tags       = merge(local.default_tags, local.admin_component_tag)
}

resource "aws_lb" "admin" {
  name = "${local.environment}-admin"
  #tfsec:ignore:AWS005 - public facing load balancer
  internal                   = false
  load_balancer_type         = "application"
  subnets                    = data.aws_subnet_ids.public.ids
  tags                       = merge(local.default_tags, local.admin_component_tag)
  drop_invalid_header_fields = true
  security_groups = [
    aws_security_group.admin_loadbalancer.id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "${local.environment}-admin"
    enabled = true
  }
}

resource "aws_lb_listener" "admin_loadbalancer" {
  load_balancer_arn = aws_lb.admin.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06"

  certificate_arn = data.aws_acm_certificate.certificate_admin.arn

  default_action {
    target_group_arn = aws_lb_target_group.admin.arn
    type             = "forward"
  }
}

#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "admin_loadbalancer" {
  name        = "${local.environment}-admin-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = data.aws_vpc.default.id
  tags        = merge(local.default_tags, local.admin_component_tag)
}

#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "admin_loadbalancer_ingress" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.allowed_ip_list.moj_sites
  security_group_id = aws_security_group.admin_loadbalancer.id
}

#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "admin_loadbalancer_ingress_production" {
  count     = local.environment == "production" ? 1 : 0
  type      = "ingress"
  from_port = 443
  to_port   = 443
  protocol  = "tcp"
  #tfsec:ignore:AWS006 - public facing inbound rule
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.admin_loadbalancer.id
}

#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "admin_loadbalancer_egress" {
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:AWS007 - public facing load balancer
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.admin_loadbalancer.id
}
