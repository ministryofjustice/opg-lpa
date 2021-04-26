resource "aws_security_group" "redis_cache_service" {
  name   = "redis-cache-service"
  vpc_id = data.aws_vpc.default.id
  tags   = local.front_component_tag
}

resource "aws_elasticache_subnet_group" "private_subnets" {
  name       = "private-subnets"
  subnet_ids = data.aws_subnet_ids.private[*].ids
}


resource "aws_elasticache_replication_group" "redis_cache" {
  replication_group_id          = "redis-cache-replication-group"
  replication_group_description = "redis cache replication group"
  parameter_group_name          = "default.redis5.0"
  engine                        = "redis"
  engine_version                = "5.0.6"
  node_type                     = "cache.t2.micro"
  number_cache_clusters         = 2
  transit_encryption_enabled    = true
  at_rest_encryption_enabled    = true
  automatic_failover_enabled    = true

  subnet_group_name  = aws_elasticache_subnet_group.private_subnets.name
  security_group_ids = [aws_security_group.redis_cache_service.id]

  tags = local.front_component_tag
}
