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

moved {
  from = module.eu-west-1.module.vpc_endpoints.aws_vpc_endpoint.cloudshell["codecatalyst.packages"]
  to   = module.eu-west-1.module.vpc_endpoints.aws_vpc_endpoint.codecatalyst["codecatalyst.packages"]
}

moved {
  from = module.eu-west-1.module.vpc_endpoints.aws_vpc_endpoint.cloudshell["codecatalyst.git"]
  to   = module.eu-west-1.module.vpc_endpoints.aws_vpc_endpoint.codecatalyst["codecatalyst.git"]
}

removed {
  from = module.eu-west-1.aws_ebs_snapshot_block_public_access.this
  lifecycle {
    destroy = false
  }
}

removed {
  from = module.eu-west-2.aws_ebs_snapshot_block_public_access.this
  lifecycle {
    destroy = false
  }
}
removed {
  from = module.eu-west-1.aws_kms_key.cloudwatch_encryption
  lifecycle {
    destroy = false
  }
}

removed {
  from = module.eu-west-2.aws_kms_key.cloudwatch_encryption
  lifecycle {
    destroy = false
  }
}


import {
  to = module.eu-west-1.aws_db_parameter_group.postgres_db_params["postgres13"]
  id = "postgres13-db-params"
}
import {
  to = module.eu-west-1.aws_db_parameter_group.postgres_db_params["postgres14"]
  id = "postgres14-db-params"
}
import {
  to = module.eu-west-1.aws_rds_cluster_parameter_group.postgresql_aurora_params["postgres13"]
  id = "postgres13-cluster-params"
}
import {
  to = module.eu-west-1.aws_rds_cluster_parameter_group.postgresql_aurora_params["postgres14"]
  id = "postgres14-cluster-params"
}
import {
  to = aws_iam_role.rds_enhanced_monitoring
  id = "rds-enhanced-monitoring"
}
import {
  to = aws_iam_role.vpc_flow_logs
  id = "vpc_flow_logs"
}
import {
  to = aws_iam_role_policy.vpc_flow_logs
  id = "vpc_flow_logs:vpc_flow_logs"
}
