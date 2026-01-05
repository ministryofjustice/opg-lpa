#Old network
#tfsec:ignore:aws-ec2-add-description-to-security-group - adding a description is a destructive change.
resource "aws_security_group" "front_cache" {
  name   = "${local.account_name_short}-${local.region_name}-front-cache"
  vpc_id = aws_default_vpc.default.id
  tags   = local.front_component_tag
}

resource "aws_elasticache_subnet_group" "private_subnets" {
  name       = "${local.account_name_short}-${local.region_name}-elasticache-private-subnets"
  subnet_ids = aws_subnet.private[*].id
}

resource "aws_elasticache_replication_group" "front_cache" {
  replication_group_id       = "${local.account_name_short}-${local.region_name}-front-cache-rg"
  description                = "front cache replication group"
  parameter_group_name       = "default.redis6.x"
  engine                     = "redis"
  engine_version             = "6.x"
  node_type                  = "cache.t2.micro"
  num_cache_clusters         = local.cache_cluster_count
  transit_encryption_enabled = true
  at_rest_encryption_enabled = true
  automatic_failover_enabled = true
  maintenance_window         = "wed:05:00-wed:09:00"
  snapshot_window            = "02:00-04:50"
  notification_topic_arn     = aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn
  subnet_group_name          = aws_elasticache_subnet_group.private_subnets.name
  security_group_ids         = [aws_security_group.front_cache.id]

  tags = local.front_component_tag
}

#New Network
#tfsec:ignore:aws-ec2-add-description-to-security-group - adding a description is a destructive change.
# resource "aws_security_group" "new_front_cache" {
#   name   = "${local.account_name_short}-${local.region_name}-new-front-cache"
#   vpc_id = module.network.vpc.id
#   tags   = local.front_component_tag
# }

# resource "aws_elasticache_subnet_group" "application_subnets" {
#   name       = "${local.account_name_short}-${local.region_name}-elasticache-application-subnets"
#   subnet_ids = module.network.application_subnets[*].id
# }

# resource "aws_elasticache_replication_group" "new_front_cache" {
#   replication_group_id       = "${local.account_name_short}-${local.region_name}-new-front-cache-rg"
#   description                = "front cache replication group"
#   parameter_group_name       = "default.redis6.x"
#   engine                     = "redis"
#   engine_version             = "6.x"
#   node_type                  = "cache.t2.micro"
#   num_cache_clusters         = local.cache_cluster_count
#   transit_encryption_enabled = true
#   at_rest_encryption_enabled = true
#   automatic_failover_enabled = true
#   maintenance_window         = "wed:05:00-wed:09:00"
#   snapshot_window            = "02:00-04:50"
#   notification_topic_arn     = aws_sns_topic.cloudwatch_to_slack_elasticache_alerts.arn
#   subnet_group_name          = aws_elasticache_subnet_group.application_subnets.name
#   security_group_ids         = [aws_security_group.new_front_cache.id]

#   tags = local.front_component_tag
# }

locals {
  cache_cluster_count   = 2
  cache_member_clusters = tolist(aws_elasticache_replication_group.front_cache.member_clusters)
}
