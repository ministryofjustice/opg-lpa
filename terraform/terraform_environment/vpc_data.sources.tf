data "aws_vpc" "default" {
  default = true
}

data "aws_subnet_ids" "private" {
  vpc_id = data.aws_vpc.default.id

  tags = {
    Name = "private*"
  }
}

data "aws_subnet_ids" "public" {
  vpc_id = data.aws_vpc.default.id

  tags = {
    Name = "public*"
  }
}

module "whitelist" {
  source = "git@github.com:ministryofjustice/terraform-aws-moj-ip-whitelist.git"
}

data "aws_s3_bucket" "access_log" {
  bucket = "online-lpa-${local.account_name}-lb-access-logs"
}

data "aws_s3_bucket" "lpa_pdf_cache" {
  bucket = lower("online-lpa-pdf-cache-${local.account_name}")
}

data "aws_kms_key" "lpa_pdf_cache" {
  key_id = "alias/lpa_pdf_cache-${local.account_name}"
}

data "aws_acm_certificate" "certificate_front" {
  domain = var.accounts[local.account_name].front_certificate_domain_name
}

data "aws_acm_certificate" "certificate_admin" {
  domain = var.accounts[local.account_name].admin_certificate_domain_name
}

data "aws_acm_certificate" "certificate_live_service" {
  count  = terraform.workspace == "production" ? 1 : 0
  domain = "*.lastingpowerofattorney.service.gov.uk"
}
