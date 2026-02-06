moved {
  from = module.eu-west-1.module.api_aurora[0].aws_rds_cluster_parameter_group.postgres-aurora-params
  to   = module.eu-west-1.module.api_aurora[0].aws_rds_cluster_parameter_group.postgres13-aurora-params
}

moved {
  from = module.eu-west-1.module.api_aurora[0].aws_rds_cluster_parameter_group.postgres13-aurora-params
  to   = module.eu-west-1.aws_rds_cluster_parameter_group.postgresql_aurora_params["postgres13"]
}

moved {
  from = module.eu-west-1.module.api_aurora[0].aws_rds_cluster_parameter_group.postgres14-aurora-params
  to   = module.eu-west-1.aws_rds_cluster_parameter_group.postgresql_aurora_params["postgres14"]
}

moved {
  from = module.eu-west-1.aws_db_parameter_group.postgres13-db-params
  to   = module.eu-west-1.aws_db_parameter_group.postgres_db_params["postgres13"]
}

moved {
  from = module.eu-west-1.aws_db_parameter_group.postgres14-db-params
  to   = module.eu-west-1.aws_db_parameter_group.postgres_db_params["postgres14"]
}

moved {
  from = aws_iam_role.restore_testing_role
  to   = module.cross_region_backup[0].aws_iam_role.restore_testing_role
}
