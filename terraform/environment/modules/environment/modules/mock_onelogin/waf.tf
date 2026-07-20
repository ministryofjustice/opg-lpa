data "aws_wafv2_web_acl" "main" {
  provider = aws.region
  name     = "${var.account_name}-${data.aws_region.current.region}-web-acl"
  scope    = "REGIONAL"
}

resource "aws_wafv2_web_acl_association" "app" {
  count        = var.waf_alb_association_enabled ? 1 : 0
  provider     = aws.region
  resource_arn = aws_lb.mock_onelogin.arn
  web_acl_arn  = data.aws_wafv2_web_acl.main.arn
}
