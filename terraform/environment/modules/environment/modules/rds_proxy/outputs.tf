output "endpoint" {
  value = aws_db_proxy.rds_proxy.endpoint
}

output "read_only_endpoint" {
  description = "RDS Proxy endpoint for read-only requests"
  value       = aws_db_proxy_endpoint.rds_read_only.endpoint
}
