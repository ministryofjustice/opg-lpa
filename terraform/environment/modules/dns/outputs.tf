output "admin_domain" {
  value = "https://${aws_route53_record.admin.fqdn}"
}

output "admin_fqdn" {
  value = aws_route53_record.admin.fqdn
}

output "front_domain" {
  value = "https://${aws_route53_record.front.fqdn}/home"
}

output "front_fqdn" {
  value = aws_route53_record.front.fqdn
}

output "public_facing_lastingpowerofattorney_domain" {
  value = "https://${aws_route53_record.public_facing_lastingpowerofattorney.fqdn}/home"
}

output "public_facing_lastingpowerofattorney_fqdn" {
  value = aws_route53_record.public_facing_lastingpowerofattorney.fqdn
}

