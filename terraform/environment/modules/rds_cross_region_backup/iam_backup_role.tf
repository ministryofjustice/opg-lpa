data "aws_iam_role" "aurora_backup_role" {
  name = "aurora_cluster_backup_role"
}

#tfsec:ignore:aws-iam-no-policy-wildcards - the iam policy restrictions are being implemented through kms key policies
data "aws_iam_policy_document" "aurora_backup_role" {
  statement {
    actions = ["kms:Encrypt", "kms:CreateGrant", "kms:Decrypt", "kms:ReEncrypt*", "kms:GenerateDataKey*", "kms:DescribeKey"]

    resources = [
      data.aws_kms_key.rds_encryption_key_primary.arn,
      data.aws_kms_key.rds_encryption_key_replica.arn,
      data.aws_kms_key.cross_account_backup_key.arn,
    ]
  }
}


resource "aws_iam_policy" "aurora_backup_resources" {
  name        = "${var.environment_name}_aurora_backup_role_policy"
  description = "Policies for aurora backup role"
  policy      = data.aws_iam_policy_document.aurora_backup_role.json
}

resource "aws_iam_role_policy_attachment" "aurora_backup_resources" {
  role       = data.aws_iam_role.aurora_backup_role.name
  policy_arn = aws_iam_policy.aurora_backup_resources.arn
}
