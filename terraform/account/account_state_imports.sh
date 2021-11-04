# pull the state down for account. We will not be pushing this back up at this stage.
# there will be a state rm later for anything not region level.
aws-vault exec identity -- terraform state pull > account.tfstate
cp account.tfstate account.tfstate.backup

cd ../region

aws-vault exec identity -- terraform state pull > region.tfstate
cp region.tfstate region.tfstate.backup
# needed to import as existing GW is a data reference.
aws-vault exec identity -- terraform import -state-out=region.tfstate "module.eu-west-1.aws_internet_gateway.default" igw-7f98a318

cd ../account

aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_cloudwatch_log_group.vpc_flow_logs" "module.eu-west-1.aws_cloudwatch_log_group.vpc_flow_logs"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_flow_log.vpc_flow_logs" "module.eu-west-1.aws_flow_log.vpc_flow_logs"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_db_subnet_group.data_persistence" "module.eu-west-1.aws_db_subnet_group.data_persistence"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_default_route_table.default" "module.eu-west-1.aws_default_route_table.default"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_default_subnet.public[0]" "module.eu-west-1.aws_default_subnet.public[0]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_default_subnet.public[1]" "module.eu-west-1.aws_default_subnet.public[1]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_default_subnet.public[2]" "module.eu-west-1.aws_default_subnet.public[2]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_default_vpc.default" "module.eu-west-1.aws_default_vpc.default"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_eip.nat[0]" "module.eu-west-1.aws_eip.nat[0]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_eip.nat[1]" "module.eu-west-1.aws_eip.nat[1]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_eip.nat[2]" "module.eu-west-1.aws_eip.nat[2]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_kms_alias.lpa_pdf_cache" "module.eu-west-1.aws_kms_alias.lpa_pdf_cache"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_kms_key.lpa_pdf_cache" "module.eu-west-1.aws_kms_key.lpa_pdf_cache"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_kms_key.secrets_encryption_key" "module.eu-west-1.aws_kms_key.secrets_encryption_key"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_nat_gateway.nat[0]" "module.eu-west-1.aws_nat_gateway.nat[0]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_nat_gateway.nat[1]" "module.eu-west-1.aws_nat_gateway.nat[1]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_nat_gateway.nat[2]" "module.eu-west-1.aws_nat_gateway.nat[2]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route.default" "module.eu-west-1.aws_route.default"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route.private[0]" "module.eu-west-1.aws_route.private[0]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route.private[1]" "module.eu-west-1.aws_route.private[1]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route.private[2]" "module.eu-west-1.aws_route.private[2]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route_table.private[0]" "module.eu-west-1.aws_route_table.private[0]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route_table.private[1]" "module.eu-west-1.aws_route_table.private[1]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route_table.private[2]" "module.eu-west-1.aws_route_table.private[2]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route_table_association.private[0]" "module.eu-west-1.aws_route_table_association.private[0]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route_table_association.private[1]" "module.eu-west-1.aws_route_table_association.private[1]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_route_table_association.private[2]" "module.eu-west-1.aws_route_table_association.private[2]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.api_rds_password" "module.eu-west-1.aws_secretsmanager_secret.api_rds_password"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.api_rds_username" "module.eu-west-1.aws_secretsmanager_secret.api_rds_username"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_admin_jwt_secret" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_admin_jwt_secret"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_api_notify_api_key" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_api_notify_api_key"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_common_account_cleanup_notification_recipients" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_common_account_cleanup_notification_recipients"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_common_admin_accounts" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_common_admin_accounts"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_front_csrf_salt" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_front_csrf_salt"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_front_email_sendgrid_api_key" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_front_email_sendgrid_api_key"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_front_email_sendgrid_webhook_token" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_front_email_sendgrid_webhook_token"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_front_gov_pay_key" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_front_gov_pay_key"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_front_os_places_hub_license_key" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_front_os_places_hub_license_key"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.opg_lpa_pdf_owner_password" "module.eu-west-1.aws_secretsmanager_secret.opg_lpa_pdf_owner_password"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.performance_platform_db_password" "module.eu-west-1.aws_secretsmanager_secret.performance_platform_db_password"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_secretsmanager_secret.performance_platform_db_username" "module.eu-west-1.aws_secretsmanager_secret.performance_platform_db_username"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_subnet.private[0]" "module.eu-west-1.aws_subnet.private[0]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_subnet.private[1]" "module.eu-west-1.aws_subnet.private[1]"
aws-vault exec identity -- terraform state mv -state=account.tfstate -state-out=../region/region.tfstate  "aws_subnet.private[2]" "module.eu-west-1.aws_subnet.private[2]"
