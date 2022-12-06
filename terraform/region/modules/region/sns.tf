#tfsec:ignore:aws-sns-enable-topic-encryption unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_slack_elasticache_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-${local.region_name}-elasticache-alert"
  tags = local.front_component_tag
}

#tfsec:ignore:aws-sns-enable-topic-encryption unsupported for this type of alert
resource "aws_sns_topic" "cloudwatch_to_account_ops_alerts" {
  name = "CloudWatch-to-PagerDuty-${local.account_name}-${local.region_name}-ops-alert"
  tags = local.shared_component_tag
}

#tfsec:ignore:aws-sns-enable-topic-encryption - review if changed to Aurora
resource "aws_sns_topic" "rds_events" {
  name = "${local.account_name}-${local.region_name}-rds-events"
  tags = local.db_component_tag
}

# All events split into different subscriptions by source type to allow filtering based on source type and event type

#tfsec:ignore:aws-sns-enable-topic-encryption - - review if changed to Aurora
resource "aws_db_event_subscription" "rds_events_db_cluster" {
  name      = "${local.account_name}-${local.region_name}-rds-event-db-cluster-sub"
  sns_topic = aws_sns_topic.rds_events.arn

  source_type = "db-cluster"
}

#tfsec:ignore:aws-sns-enable-topic-encryption - - review if changed to Aurora
resource "aws_db_event_subscription" "rds_events_db_instance" {
  name      = "${local.account_name}-${local.region_name}-rds-event-db-instance-sub"
  sns_topic = aws_sns_topic.rds_events.arn

  source_type = "db-instance"
}

#tfsec:ignore:aws-sns-enable-topic-encryption - - review if changed to Aurora
resource "aws_db_event_subscription" "rds_events_db_sg" {
  name      = "${local.account_name}-${local.region_name}-rds-event-db-sg-sub"
  sns_topic = aws_sns_topic.rds_events.arn

  source_type = "db-security-group"
}

#tfsec:ignore:aws-sns-enable-topic-encryption - - review if changed to Aurora
resource "aws_db_event_subscription" "rds_events_db_pg" {
  name      = "${local.account_name}-${local.region_name}-rds-event-db-pg-sub"
  sns_topic = aws_sns_topic.rds_events.arn

  source_type = "db-parameter-group"
}

#tfsec:ignore:aws-sns-enable-topic-encryption - - review if changed to Aurora
resource "aws_db_event_subscription" "rds_events_db_snapshot" {
  name      = "${local.account_name}-${local.region_name}-rds-event-db-snapshot-sub"
  sns_topic = aws_sns_topic.rds_events.arn

  source_type = "db-snapshot"

  event_categories = [
    "notification",
  ]
}

#tfsec:ignore:aws-sns-enable-topic-encryption - - review if changed to Aurora
resource "aws_db_event_subscription" "rds_events_db_cluster_snapshot" {
  name      = "${local.account_name}-${local.region_name}-rds-event-db-cluster-snapshot-sub"
  sns_topic = aws_sns_topic.rds_events.arn

  source_type = "db-cluster-snapshot"

  event_categories = [
    "notification",
  ]
}