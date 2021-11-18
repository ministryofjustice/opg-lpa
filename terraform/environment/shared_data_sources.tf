data "aws_security_group" "front_cache" {
  name = "${local.account_name_short}-${local.region_name}-front-cache"
}

data "aws_elasticache_replication_group" "front_cache" {
  replication_group_id = "${local.account_name_short}-${local.region_name}-front-cache-rg"
}
