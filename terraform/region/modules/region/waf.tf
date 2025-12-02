resource "aws_wafv2_web_acl" "main" {
  name        = "${local.account_name}-${local.region_name}-web-acl"
  description = "Managed rules"
  scope       = "REGIONAL"

  default_action {
    allow {}
  }

  rule {
    name     = "Allow-ON-Keyword"
    priority = 5

    action {
      allow {}
    }

    statement {
      regex_pattern_set_reference_statement {
        arn = aws_wafv2_regex_pattern_set.allow_on_keyword.arn

        field_to_match {
          body {}
        }

        text_transformation {
          priority = 0
          type     = "NONE"
        }
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "allow-on-keyword"
      sampled_requests_enabled   = true
    }

  }

  rule {
    name     = "AWS-AWSManagedRulesPHPRuleSet"
    priority = 10

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesPHPRuleSet"
        vendor_name = "AWS"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "AWS-AWSManagedRulesPHPRuleSet"
      sampled_requests_enabled   = true
    }
  }

  rule {
    name     = "AWS-AWSManagedRulesKnownBadInputsRuleSet"
    priority = 20

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesKnownBadInputsRuleSet"
        vendor_name = "AWS"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "AWS-AWSManagedRulesKnownBadInputsRuleSet"
      sampled_requests_enabled   = true
    }
  }

  rule {
    name     = "AWS-AWSManagedRulesCommonRuleSet"
    priority = 30

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesCommonRuleSet"
        vendor_name = "AWS"

        rule_action_override {
          name = "NoUserAgent_HEADER"
          action_to_use {
            count {}
          }
        }
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "AWS-AWSManagedRulesCommonRuleSet"
      sampled_requests_enabled   = true
    }
  }

  rule {
    name     = "AWS-AWSManagedRulesAmazonIpReputationList"
    priority = 40

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesAmazonIpReputationList"
        vendor_name = "AWS"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "AWS-AWSManagedRulesAmazonIpReputationList"
      sampled_requests_enabled   = true
    }
  }

  rule {
    name     = "AWS-AWSManagedRulesSQLiRuleSet"
    priority = 50

    override_action {
      none {}
    }

    statement {
      managed_rule_group_statement {
        name        = "AWSManagedRulesSQLiRuleSet"
        vendor_name = "AWS"
      }
    }

    visibility_config {
      cloudwatch_metrics_enabled = true
      metric_name                = "AWS-AWSManagedRulesSQLiRuleSet"
      sampled_requests_enabled   = true
    }
  }

  visibility_config {
    cloudwatch_metrics_enabled = true
    metric_name                = "${local.account_name}-${local.region_name}-web-acl"
    sampled_requests_enabled   = true
  }
}

// Avoid false-positives for the AWSManagedRulesCommonRuleSet rule - see AWS support ticket 11374935181
resource "aws_wafv2_regex_pattern_set" "allow_on_keyword" {
  name        = "on-keyword"
  description = "On keyword regex pattern set"
  scope       = "REGIONAL"

  regular_expression {
    regex_string = ".*[oO][nN].*=.*$"
  }

}

resource "aws_wafv2_web_acl_logging_configuration" "main" {
  log_destination_configs = [aws_cloudwatch_log_group.waf_web_acl.arn]
  resource_arn            = aws_wafv2_web_acl.main.arn
}

resource "aws_cloudwatch_log_group" "waf_web_acl" {
  name              = "aws-waf-logs-${local.account_name}-${local.region_name}"
  retention_in_days = 120
  kms_key_id        = aws_kms_key.waf_cloudwatch_log_encryption.arn
  tags = {
    "Name" = "${local.account_name}-${local.region_name}-web-acl"
  }
}

resource "aws_kms_key" "waf_cloudwatch_log_encryption" {
  description             = "AWS WAF Cloudwatch encryption ${local.account_name} in ${local.region_name}"
  deletion_window_in_days = 10
  enable_key_rotation     = true
  policy                  = data.aws_iam_policy_document.waf_cloudwatch_log_encryption_kms.json
}

resource "aws_kms_alias" "waf_cloudwatch_log_encryption" {
  name          = "alias/waf_cloudwatch_log_encryption"
  target_key_id = aws_kms_key.waf_cloudwatch_log_encryption.key_id
}

# See the following link for further information
# https://docs.aws.amazon.com/kms/latest/developerguide/key-policies.html
data "aws_iam_policy_document" "waf_cloudwatch_log_encryption_kms" {
  statement {
    sid       = "Enable Root account permissions on Key"
    effect    = "Allow"
    actions   = ["kms:*"]
    resources = ["*"]

    principals {
      type = "AWS"
      identifiers = [
        "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root",
      ]
    }
  }

  statement {
    sid       = "Allow Key to be used for Encryption"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Encrypt",
      "kms:Decrypt",
      "kms:ReEncrypt*",
      "kms:GenerateDataKey*",
      "kms:DescribeKey",
    ]

    principals {
      type = "Service"
      identifiers = [
        "logs.${data.aws_region.current.region}.amazonaws.com",
        "events.amazonaws.com"
      ]
    }
  }

  statement {
    sid       = "Key Administrator"
    effect    = "Allow"
    resources = ["*"]
    actions = [
      "kms:Create*",
      "kms:Describe*",
      "kms:Enable*",
      "kms:List*",
      "kms:Put*",
      "kms:Update*",
      "kms:Revoke*",
      "kms:Disable*",
      "kms:Get*",
      "kms:Delete*",
      "kms:TagResource",
      "kms:UntagResource",
      "kms:ScheduleKeyDeletion",
      "kms:CancelKeyDeletion"
    ]

    principals {
      type        = "AWS"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/breakglass"]
    }
  }
}
