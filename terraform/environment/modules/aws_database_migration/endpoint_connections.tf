# resource "aws_dms_endpoint_connection" "source" {
#   count                    = var.test_connections ? 1 : 0
#   endpoint_arn             = aws_dms_endpoint.source.endpoint_arn
#   replication_instance_arn = aws_dms_replication_instance.aurora_migration.replication_instance_arn
# }

# resource "aws_dms_endpoint_connection" "target" {
#   count                    = var.test_connections ? 1 : 0
#   endpoint_arn             = aws_dms_endpoint.target.endpoint_arn
#   replication_instance_arn = aws_dms_replication_instance.aurora_migration.replication_instance_arn
# }
