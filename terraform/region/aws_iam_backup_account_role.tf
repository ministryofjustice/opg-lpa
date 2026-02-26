resource "aws_iam_role" "make_cross_account_backup_role" {
  provider           = aws.backup
  name               = "${local.environment_name}-make-cross-account-backup-role"
  assume_role_policy = data.aws_iam_policy_document.make-cross-account-backup-role-permissions.json
}

resource "aws_iam_role_policy_attachment" "make_cross_account_backup_role" {
  role       = aws_iam_role.make_cross_account_backup_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForBackup"
}

data "aws_iam_policy_document" "make-cross-account-backup-role-permissions" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["backup.amazonaws.com"]
    }
  }
}
