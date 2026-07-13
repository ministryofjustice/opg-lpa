resource "aws_wafv2_ip_set" "blocked_ips" {
  name               = "BlockedIPs"
  description        = "IPs to block using the WAF"
  scope              = "REGIONAL"
  ip_address_version = "IPV4"
  addresses          = []

  lifecycle {
    ignore_changes = [
      addresses,
      description
    ]
  }
}
