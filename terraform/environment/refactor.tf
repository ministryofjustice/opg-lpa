moved {
  from = module.eu-west-1.module.api_aurora[0].aws_rds_cluster_parameter_group.postgres-aurora-params
  to   = module.eu-west-1.module.api_aurora[0].aws_rds_cluster_parameter_group.postgres13-aurora-params
}

moved {
  from = module.eu-west-1.module.api_aurora[0].aws_rds_cluster_parameter_group.postgres13-aurora-params
  to   = module.eu-west-1.aws_rds_cluster_parameter_group.postgresql13-aurora-params
}

moved {
  from = module.eu-west-1.module.api_aurora[0].aws_rds_cluster_parameter_group.postgres14-aurora-params
  to   = module.eu-west-1.aws_rds_cluster_parameter_group.postgresql14-aurora-params
}
