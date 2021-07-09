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
  depends_on = [aws_lb.front]
  tags       = merge(local.default_tags, local.front_component_tag)
}

resource "aws_lb" "front" {
  name = "${local.environment}-front"
  #tfsec:ignore:AWS005
  internal           = false
  load_balancer_type = "application"
  subnets            = data.aws_subnet_ids.public.ids
  tags               = merge(local.default_tags, local.front_component_tag)


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
  tags        = merge(local.default_tags, local.front_component_tag)

}

resource "aws_security_group_rule" "front_loadbalancer_ingress" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.allowed_ip_list.moj_sites
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

// Allow http traffic in to be redirected to https
resource "aws_security_group_rule" "front_loadbalancer_ingress_http" {
  type              = "ingress"
  from_port         = 80
  to_port           = 80
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
  listener_arn    = aws_lb_listener.front_loadbalancer.arn
  certificate_arn = data.aws_acm_certificate.public_facing_certificate.arn
}


# maintenance site switching

resource "aws_lb_listener_rule" "front_maintenance" {
  listener_arn = aws_lb_listener.front_loadbalancer.arn
  priority     = 101 # Specifically set so that maintenance mode scripts can locate the correct rule to modify
  action {
    type = "fixed-response"

    fixed_response {
      content_type = "text/html"
      message_body = file("${path.module}/maintenance/maintenance.html")
      status_code  = "503"
    }
  }

  condition {
    path_pattern {
      values = ["/maintenance"]
    }
  }
  lifecycle {
    ignore_changes = [
      # Ignore changes to the condition as this is modified by a script
      # when putting the service into maintenance mode.
      condition,
    ]
  }
}

//------------------------------------------------
// HTTP Redirect to HTTPS

resource "aws_lb_listener" "front_loadbalancer_http_redirect" {
  load_balancer_arn = aws_lb.front.arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type = "redirect"

    redirect {
      port        = 443
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
}

//------------------------------------------------
// WWW Redirect

resource "aws_lb_listener_rule" "www_redirect" {
  listener_arn = aws_lb_listener.front_loadbalancer.arn
  priority     = 100

  action {
    type = "redirect"

    redirect {
      host        = "www.lastingpowerofattorney.service.gov.uk"
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
  condition {
    host_header {
      values = ["lastingpowerofattorney.service.gov.uk"]
    }
  }
}
