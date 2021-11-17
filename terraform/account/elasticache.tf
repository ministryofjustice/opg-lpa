#tfsec:ignore:AWS018 - adding a description is a destructive change.
resource "aws_security_group" "front_cache" {
  name   = "front-cache"
  vpc_id = data.aws_vpc.default.id
  tags   = merge(local.default_tags, local.front_component_tag)
}

resource "aws_elasticache_subnet_group" "private_subnets" {
  name       = "private-subnets"
  subnet_ids = data.aws_subnet_ids.private.ids
}


resource "aws_elasticache_replication_group" "front_cache" {
  replication_group_id          = "front-cache-replication-group"
  replication_group_description = "front cache replication group"
  parameter_group_name          = "default.redis5.0"
  engine                        = "redis"
  engine_version                = "5.0.6"
  node_type                     = "cache.t2.micro"
  number_cache_clusters         = local.cache_cluster_count
  transit_encryption_enabled    = true
  at_rest_encryption_enabled    = true
  automatic_failover_enabled    = true
  maintenance_window            = "wed:05:00-wed:09:00"
  snapshot_window               = "02:00-04:50"
  notification_topic_arn        = aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn
  subnet_group_name             = aws_elasticache_subnet_group.private_subnets.name
  security_group_ids            = [aws_security_group.front_cache.id]

  tags = merge(local.default_tags, local.front_component_tag)
}


locals {
  cache_cluster_count   = 2
  cache_member_clusters = tolist(aws_elasticache_replication_group.front_cache.member_clusters)
}
