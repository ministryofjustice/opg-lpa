resource "aws_lb_target_group" "mock_onelogin" {
  name                 = "${data.aws_default_tags.current.tags.environment-name}-mock-onelogin"
  port                 = 80
  protocol             = "HTTP"
  target_type          = "ip"
  vpc_id               = var.network.vpc_id
  deregistration_delay = 300
  depends_on           = [aws_lb.mock_onelogin]

  health_check {
    enabled = true
    path    = "/.well-known/openid-configuration"
  }

  provider = aws.region
}

resource "aws_lb" "mock_onelogin" {
  name                       = "${data.aws_default_tags.current.tags.environment-name}-mock-onelogin"
  internal                   = false #tfsec:ignore:AWS005 - public alb
  load_balancer_type         = "application"
  drop_invalid_header_fields = true
  subnets                    = var.network.public_subnets
  enable_deletion_protection = var.alb_deletion_protection_enabled
  security_groups            = [aws_security_group.mock_onelogin_loadbalancer.id]

  access_logs {
    bucket  = data.aws_s3_bucket.access_log.bucket
    prefix  = "mock-onelogin-${data.aws_default_tags.current.tags.environment-name}"
    enabled = true
  }
  provider = aws.region
}

resource "aws_lb_listener" "mock_onelogin_loadbalancer_http_redirect" {
  load_balancer_arn = aws_lb.mock_onelogin.arn
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
  provider = aws.region
}

data "aws_acm_certificate" "certificate_mock_onelogin" {
  domain   = "*.app.modernising.opg.service.justice.gov.uk"
  provider = aws.region
}

resource "aws_lb_listener" "mock_onelogin_loadbalancer" {
  load_balancer_arn = aws_lb.mock_onelogin.arn
  port              = "443"
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-FS-1-2-2019-08"
  certificate_arn   = data.aws_acm_certificate.certificate_mock_onelogin.arn

  default_action {
    target_group_arn = aws_lb_target_group.mock_onelogin.arn
    type             = "forward"
  }
  provider = aws.region
}

resource "aws_lb_listener_certificate" "mock_onelogin_loadbalancer_live_service_certificate" {
  listener_arn    = aws_lb_listener.mock_onelogin_loadbalancer.arn
  certificate_arn = data.aws_acm_certificate.certificate_mock_onelogin.arn
  provider        = aws.region
}

resource "aws_security_group" "mock_onelogin_loadbalancer" {
  name_prefix = "${data.aws_default_tags.current.tags.environment-name}-mock-onelogin-loadbalancer"
  description = "mock-onelogin service application load balancer"
  vpc_id      = var.network.vpc_id
  lifecycle {
    create_before_destroy = true
  }
  provider = aws.region
}

data "aws_ip_ranges" "route53_healthchecks" {
  services = ["route53_healthchecks"]
  regions  = ["GLOBAL", "us-east-1", "eu-west-1", "us-west-2"]
  provider = aws.region
}

resource "terraform_data" "route53_healthchecks_cidr_blocks" {
  input = data.aws_ip_ranges.route53_healthchecks.cidr_blocks
}

resource "terraform_data" "route53_healthchecks_ipv6_cidr_blocks" {
  input = data.aws_ip_ranges.route53_healthchecks.ipv6_cidr_blocks
}

resource "terraform_data" "ingress_allow_list_cidr" {
  input = var.ingress_allow_list_cidr
}

resource "aws_security_group_rule" "mock_onelogin_loadbalancer_port_80_redirect_ingress" {
  count             = var.public_access_enabled ? 0 : 1
  description       = "Port 80 ingress for redirection to port 443"
  type              = "ingress"
  from_port         = 80
  to_port           = 80
  protocol          = "tcp"
  cidr_blocks       = var.ingress_allow_list_cidr #tfsec:ignore:aws-vpc-no-public-ingress-sgr
  security_group_id = aws_security_group.mock_onelogin_loadbalancer.id
  lifecycle {
    replace_triggered_by = [
      terraform_data.ingress_allow_list_cidr
    ]
  }
  provider = aws.region
}

resource "aws_security_group_rule" "mock_onelogin_loadbalancer_ingress" {
  count             = var.public_access_enabled ? 0 : 1
  description       = "Port 443 ingress from the allow list to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = var.ingress_allow_list_cidr #tfsec:ignore:aws-vpc-no-public-ingress-sgr
  security_group_id = aws_security_group.mock_onelogin_loadbalancer.id
  lifecycle {
    replace_triggered_by = [
      terraform_data.ingress_allow_list_cidr
    ]
  }
  provider = aws.region
}

resource "aws_security_group_rule" "loadbalancer_ingress_route53_healthchecks" {
  description       = "Loadbalancer ingresss from Route53 healthchecks"
  type              = "ingress"
  protocol          = "tcp"
  from_port         = "443"
  to_port           = "443"
  cidr_blocks       = data.aws_ip_ranges.route53_healthchecks.cidr_blocks
  ipv6_cidr_blocks  = data.aws_ip_ranges.route53_healthchecks.ipv6_cidr_blocks
  security_group_id = aws_security_group.mock_onelogin_loadbalancer.id
  lifecycle {
    replace_triggered_by = [
      terraform_data.route53_healthchecks_cidr_blocks,
      terraform_data.route53_healthchecks_ipv6_cidr_blocks
    ]
  }
  provider = aws.region
}

resource "aws_security_group_rule" "mock_onelogin_loadbalancer_public_access_ingress" {
  count             = var.public_access_enabled ? 1 : 0
  description       = "Port 443 production public ingress to the application load balancer"
  type              = "ingress"
  from_port         = 443
  to_port           = 443
  protocol          = "tcp"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-vpc-no-public-ingress-sgr - open ingress for production
  security_group_id = aws_security_group.mock_onelogin_loadbalancer.id
  provider          = aws.region
}

resource "aws_security_group_rule" "mock_onelogin_loadbalancer_egress" {
  description       = "Allow any egress from service load balancer"
  type              = "egress"
  from_port         = 0
  to_port           = 0
  protocol          = "-1"
  cidr_blocks       = ["0.0.0.0/0"] #tfsec:ignore:aws-ec2-no-public-egress-sgr - open egress for load balancers
  security_group_id = aws_security_group.mock_onelogin_loadbalancer.id
  provider          = aws.region
}
