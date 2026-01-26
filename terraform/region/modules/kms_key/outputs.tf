output "eu_west_1" {
  value = aws_kms_key.eu_west_1
}

output "eu_west_2" {
  value = aws_kms_replica_key.eu_west_2
}

output "backup" {
  value = aws_kms_key.backup
}
