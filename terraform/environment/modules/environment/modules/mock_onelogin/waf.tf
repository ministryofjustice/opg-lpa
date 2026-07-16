data "aws_wafv2_web_acl" "main" {
  provider = aws.region
  name     = "${data.aws_default_tags.current.tags.account-name}-web-acl"
  scope    = "REGIONAL"
}

resource "aws_wafv2_web_acl_association" "app" {
  count        = var.waf_alb_association_enabled ? 1 : 0
  provider     = aws.region
  resource_arn = aws_lb.mock_onelogin.arn
  web_acl_arn  = data.aws_wafv2_web_acl.main.arn
}
