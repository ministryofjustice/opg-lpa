data "aws_security_group" "front_cache" {
  name = "front-cache"
}

data "aws_elasticache_replication_group" "front_cache" {
  replication_group_id = "front-cache-replication-group"
}
