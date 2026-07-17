resource "aws_route53_record" "mock_onelogin" {
  count    = data.aws_default_tags.current.tags.environment-name != "production" ? 1 : 0
  provider = aws.management
  zone_id  = data.aws_route53_zone.opg_service_justice_gov_uk.zone_id
  name     = "${data.aws_default_tags.current.tags.environment-name}${var.account_name}.onelogin.lpa"
  type     = "A"

  alias {
    evaluate_target_health = false
    name                   = aws_lb.mock_onelogin.dns_name
    zone_id                = aws_lb.mock_onelogin.zone_id
  }

  lifecycle {
    create_before_destroy = true
  }
}
