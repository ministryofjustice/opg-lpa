data "aws_security_group" "redis_cache_service" {
  name = "redis-cache-service"
}

data "aws_elasticache_replication_group" "redis_cache" {
  replication_group_id = "redis-cache-replication-group"
}