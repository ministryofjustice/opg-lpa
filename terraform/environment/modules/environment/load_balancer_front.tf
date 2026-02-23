resource "aws_lb_target_group" "front" {
  name                 = "${var.environment_name}-front"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = data.aws_vpc.main.id
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
  tags       = local.front_component_tag
}

resource "aws_lb" "front" {
  name = "${var.environment_name}-front"
  #tfsec:ignore:aws-elb-alb-not-public - public facing load balancer
  internal                   = false
  load_balancer_type         = "application"
  subnets                    = [for subnet in data.aws_subnet.lb : subnet.id]
  tags                       = local.front_component_tag
  drop_invalid_header_fields = true

  security_groups = [
    aws_security_group.front_loadbalancer.id,
    aws_security_group.front_loadbalancer_route53.id,
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
  ssl_policy        = "ELBSecurityPolicy-TLS13-1-2-2021-06"

  certificate_arn = data.aws_acm_certificate.certificate_front.arn

  default_action {
    target_group_arn = aws_lb_target_group.front.arn
    type             = "forward"
  }
}


resource "aws_security_group" "front_loadbalancer_route53" {
  name_prefix = "${var.environment_name}-actor-loadbalancer-route53"
  description = "Allow Route53 healthchecks"
  vpc_id      = data.aws_vpc.main.id
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "actor_loadbalancer_ingress_route53_healthchecks" {
  description       = "Loadbalancer ingresss from Route53 healthchecks"
  type              = "ingress"
  protocol          = "tcp"
  from_port         = "443"
  to_port           = "443"
  cidr_blocks       = data.aws_ip_ranges.route53_healthchecks.cidr_blocks
  security_group_id = aws_security_group.front_loadbalancer_route53.id
}

data "aws_ip_ranges" "route53_healthchecks" {
  regions  = ["GLOBAL"]
  services = ["ROUTE53_HEALTHCHECKS"]
}

#tfsec:ignore:aws-ec2-add-description-to-security-group - Adding description is destructive change needing downtime. to be revisited
resource "aws_security_group" "front_loadbalancer" {
  name        = "${var.environment_name}-front-loadbalancer"
  description = "Allow inbound traffic"
  vpc_id      = data.aws_vpc.main.id
  tags        = local.front_component_tag
  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_security_group_rule" "front_loadbalancer_ingress" {
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = module.allowed_ip_list.moj_sites
  security_group_id = aws_security_group.front_loadbalancer.id
  description       = "MoJ sites to Front ELB - HTTPS"
}


#tfsec:ignore:aws-ec2-add-description-to-security-group - Adding description is destructive change needing downtime. to be revisited
#tfsec:ignore:aws-ec2-no-public-ingress-sgr - public facing inbound rule
resource "aws_security_group_rule" "front_loadbalancer_ingress_production" {
  count             = var.environment_name == "production" ? 1 : 0
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.front_loadbalancer.id
  description       = "Anywhere to Production Front ELB - HTTPS"
}

#tfsec:ignore:aws-ec2-no-public-ingress-sgr - public facing inbound rule
resource "aws_security_group_rule" "front_loadbalancer_ingress_http" {
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.front_loadbalancer.id
  description       = "Anywhere to Front ELB - HTTP (redirects to HTTPS)"
}


#tfsec:ignore:aws-ec2-no-public-egress-sgr - public facing load balancer - egress is being managed by a network firewall.
resource "aws_security_group_rule" "front_loadbalancer_egress" {
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"]
  security_group_id = aws_security_group.front_loadbalancer.id
  description       = "Front ELB to Anywhere - All Traffic"
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
    type = "redirect"

    redirect {
      host        = "maintenance.opg.service.justice.gov.uk"
      path        = "/en-gb/make-a-lasting-power-of-attorney"
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_302"
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
