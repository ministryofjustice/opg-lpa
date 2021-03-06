#tfsec:ignore:AWS018 - adding a description is a destructive change.
resource "aws_security_group" "front_cache" {
  name   = "front-cache"
  vpc_id = aws_default_vpc.default.id
  tags   = merge(local.default_tags, local.front_component_tag)
}

resource "aws_elasticache_subnet_group" "private_subnets" {
  name       = "private-subnets"
  subnet_ids = aws_subnet.private[*].id
}


resource "aws_elasticache_replication_group" "front_cache" {
  replication_group_id          = "front-cache-replication-group"
  replication_group_description = "front cache replication group"
  parameter_group_name          = "default.redis5.0"
  engine                        = "redis"
  engine_version                = "5.0.6"
  node_type                     = "cache.t2.micro"
  number_cache_clusters         = 2
  transit_encryption_enabled    = true
  at_rest_encryption_enabled    = true
  automatic_failover_enabled    = true

  subnet_group_name  = aws_elasticache_subnet_group.private_subnets.name
  security_group_ids = [aws_security_group.front_cache.id]

  tags = merge(local.default_tags, local.front_component_tag)
}
