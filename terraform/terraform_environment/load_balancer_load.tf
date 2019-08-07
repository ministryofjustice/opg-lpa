resource "aws_lb_target_group" "front" {
  name                 = "${local.environment}-front"
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
  depends_on = ["aws_lb.front"]
  tags       = local.default_tags
}

resource "aws_lb" "front" {
  name               = "${local.environment}-front"
  internal           = false
  load_balancer_type = "application"
  subnets            = data.aws_subnet_ids.public.ids
  tags               = local.default_tags

  security_groups = [
    aws_security_group.front_loadbalancer.id,
  ]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "${local.environment}-front"
    enabled = true
  }
}

resource "aws_lb_listener" "front_loadbalancer" {
  load_balancer_arn = aws_lb.front.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS-1-2-Ext-2018-06"

  # certificate_arn   = "${aws_acm_certificate_validation.cert.certificate_arn}"
  certificate_arn = data.aws_acm_certificate.certificate_front.arn

  default_action {
    target_group_arn = aws_lb_target_group.front.arn
    type             = "forward"
  }
}

resource "aws_security_group" "front_loadbalancer" {
  name        = "${local.environment}-front-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = data.aws_vpc.default.id
  tags        = local.default_tags
}

resource "aws_security_group_rule" "front_loadbalancer_ingress" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.whitelist.moj_sites
  security_group_id = aws_security_group.front_loadbalancer.id
}
resource "aws_security_group_rule" "front_loadbalancer_ingress_production" {
  count             = local.environment == "production" ? 1 : 0
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.front_loadbalancer.id
}

resource "aws_security_group_rule" "front_loadbalancer_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.front_loadbalancer.id
}

resource "aws_lb_listener_certificate" "front_loadbalancer_live_service_certificate" {
  count           = terraform.workspace == "production" ? 1 : 0
  listener_arn    = aws_lb_listener.front_loadbalancer.arn
  certificate_arn = data.aws_acm_certificate.certificate_live_service.arn
}
