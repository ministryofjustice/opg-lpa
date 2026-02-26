moved {
  from = module.aws_backup_cross_account_key.aws_kms_alias.eu_west_1
  to   = module.aws_backup_cross_account_key.aws_kms_alias.primary
}

moved {
  from = module.aws_backup_cross_account_key.aws_kms_alias.eu_west_2
  to   = module.aws_backup_source_account_key.aws_kms_alias.replica_alias["eu-west-2"]
}

moved {
  from = module.aws_backup_cross_account_key.aws_kms_key.eu_west_1
  to   = module.aws_backup_cross_account_key.aws_kms_key.primary
}

moved {
  from = module.aws_backup_cross_account_key.aws_kms_replica_key.eu_west_2
  to   = module.aws_backup_source_account_key.aws_kms_replica_key.replica["eu-west-2"]
}
