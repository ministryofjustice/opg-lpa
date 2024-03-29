data "aws_wafv2_web_acl" "main" {
  name  = "${var.account_name}-${var.region_name}-web-acl"
  scope = "REGIONAL"
}

resource "aws_wafv2_web_acl_association" "front" {
  count        = var.account.associate_alb_with_waf_web_acl_enabled ? 1 : 0
  resource_arn = aws_lb.front.arn
  web_acl_arn  = data.aws_wafv2_web_acl.main.arn
}

resource "aws_wafv2_web_acl_association" "admin" {
  count        = var.account.associate_alb_with_waf_web_acl_enabled ? 1 : 0
  resource_arn = aws_lb.admin.arn
  web_acl_arn  = data.aws_wafv2_web_acl.main.arn
}
