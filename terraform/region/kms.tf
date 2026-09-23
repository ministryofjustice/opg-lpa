# old aws backup kms keys - to be removed once backup snapshots in old backup vaults are deleted via lifecycle policy expiration
resource "aws_kms_key" "multi_region_db_snapshot_key" {
  enable_key_rotation = true
  provider            = aws.eu-west-1
  multi_region        = true
}

resource "aws_kms_alias" "multi_region_db_snapshot_alias" {
  name          = "alias/mrk_db_snapshot_key-${terraform.workspace}"
  target_key_id = aws_kms_key.multi_region_db_snapshot_key.key_id
}

resource "aws_kms_replica_key" "multi_region_db_snapshot_key_replica" {
  description             = "db snapshot replica key"
  deletion_window_in_days = 7
  primary_key_arn         = aws_kms_key.multi_region_db_snapshot_key.arn
  provider                = aws.eu-west-2
}

resource "aws_kms_alias" "multi_region_db_snapshot_alias_replica" {
  name          = "alias/mrk_db_snapshot_key-${terraform.workspace}"
  target_key_id = aws_kms_replica_key.multi_region_db_snapshot_key_replica.key_id
  provider      = aws.eu-west-2
}
