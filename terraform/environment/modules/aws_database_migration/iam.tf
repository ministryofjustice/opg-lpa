# dms vpc role and policy attachments
resource "aws_iam_role" "dms_vpc_role" {
  count              = var.create_iam_roles ? 1 : 0
  provider           = aws.eu_west_1
  name               = "aurora-${var.environment_name}-dms-vpc-role"
  assume_role_policy = data.aws_iam_policy_document.dms_vpc_role_permissions.json
}

resource "aws_iam_role_policy_attachment" "dms_vpc_role" {
  count      = var.create_iam_roles ? 1 : 0
  provider   = aws.eu_west_1
  role       = aws_iam_role.dms_vpc_role[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonDMSVPCManagementRole"
}
data "aws_iam_policy_document" "dms_vpc_role_permissions" {
  statement {
    sid     = "AllowAWSServicesToAssumeRole"
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      identifiers = [
        "dms.amazonaws.com",
        "dms.eu-west-1.amazonaws.com",
      ]
      type = "Service"
    }
  }
}

# dms cloudwatch role
resource "aws_iam_role" "dms_cloudwatch_role" {
  count    = var.create_iam_roles ? 1 : 0
  provider = aws.eu_west_1

  name = "aurora-${var.environment_name}-dms-cloudwatch-role"
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect    = "Allow"
        Action    = "sts:AssumeRole"
        Principal = { Service = "dms.amazonaws.com" }
      }
    ]
  })

  tags = local.common_tags
}

resource "aws_iam_role_policy_attachment" "dms_cloudwatch_role" {
  count      = var.create_iam_roles ? 1 : 0
  provider   = aws.eu_west_1
  role       = aws_iam_role.dms_cloudwatch_role[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonDMSCloudWatchLogsRole"
}


#  missing ROLE? (compared to sirius)

# module "dms_secrets_manager_kms_key" {
#   count  = local.dms_etl_enabled ? 1 : 0
#   source = "../modules/kms_key"

#   administrator_roles = local.kms_key_administrator_roles
#   alias               = "dms-secret-encryption-${local.environment_name}"
#   decryption_roles    = [aws_iam_role.dms_vpc_role[0].arn]
#   description         = "KMS Key for DMS Secrets Encryption in ${local.environment_name}"
#   encryption_roles    = local.kms_key_administrator_roles
#   usage_services = [
#     "secretsmanager.*.amazonaws.com"
#   ]

#   providers = {
#     aws.eu_west_1 = aws
#     aws.eu_west_2 = aws.eu-west-2
#   }
# }


# resource "aws_secretsmanager_secret" "admin_role_database_password" {
#   name       = "${var.environment_name}/dms/admin-role-database-password"
#   kms_key_id = data.aws_kms_key.secrets_manager_cmkms_key.arn
# }

# data "aws_secretsmanager_secret_version" "admin_role_database_password" {
#   secret_id = aws_secretsmanager_secret.admin_role_database_password.id
# }
