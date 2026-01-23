

resource "aws_iam_role" "aurora_backup_role" {
  name               = "${var.environment_name}_aurora_cluster_backup_role"
  assume_role_policy = data.aws_iam_policy_document.aurora_cluster_backup_role.json
}

resource "aws_iam_role_policy_attachment" "aurora_backup_role" {
  role       = aws_iam_role.aurora_backup_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForBackup"
}

resource "aws_iam_role_policy_attachment" "kms_aurora_backup_role" {
  role       = aws_iam_role.aurora_backup_role.name
  policy_arn = aws_iam_policy.kms_aurora_backup_role.arn
}

resource "aws_iam_policy" "kms_aurora_backup_role" {
  name        = "kms_aurora_backup_role"
  description = "KMS policy for aurora backup role"
  policy      = data.aws_iam_policy_document.aurora_backup_role.json
}

resource "aws_iam_policy" "backup_account_key" {
  name        = "backup_account_kms_key_policy"
  description = "KMS policy for cross account backup"
  policy      = data.aws_iam_policy_document.backup_account_key.json
}
