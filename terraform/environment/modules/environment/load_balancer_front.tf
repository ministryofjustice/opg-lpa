resource "aws_lb_target_group" "front" {
  name                 = "${var.environment_name}-front"
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
  tags       = merge(local.default_opg_tags, local.front_component_tag)
}

resource "aws_lb" "front" {
  name = "${var.environment_name}-front"
  #tfsec:ignore:AWS005 - public facing load balancer
  internal                   = false
  load_balancer_type         = "application"
  subnets                    = data.aws_subnet_ids.public.ids
  tags                       = merge(local.default_opg_tags, local.front_component_tag)
  drop_invalid_header_fields = true

  security_groups = [
    aws_security_group.front_loadbalancer.id,
  ]
  enable_deletion_protection = var.account_name == "development" ? false : true
  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "${var.environment_name}-front"
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

#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "front_loadbalancer" {
  name        = "${var.environment_name}-front-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = data.aws_vpc.default.id
  tags        = merge(local.default_opg_tags, local.front_component_tag)

}

#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "front_loadbalancer_ingress" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.allowed_ip_list.moj_sites
  security_group_id = aws_security_group.front_loadbalancer.id
}

#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "front_loadbalancer_ingress_production" {
  count     = var.environment_name == "production" ? 1 : 0
  type      = "ingress"
  from_port = 443
  to_port   = 443
  protocol  = "tcp"
  #tfsec:ignore:AWS006 - public facing inbound rule
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.front_loadbalancer.id
}

// Allow http traffic in to be redirected to https
#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "front_loadbalancer_ingress_http" {
  type      = "ingress"
  from_port = 80
  to_port   = 80
  protocol  = "tcp"
  #tfsec:ignore:AWS006 - public facing inbound rule
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.front_loadbalancer.id
}

//Anything out
#tfsec:ignore:AWS018 - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group_rule" "front_loadbalancer_egress" {
  type      = "egress"
  from_port = 0
  to_port   = 0
  protocol  = "-1"
  #tfsec:ignore:AWS007 - public facing load balancer
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
