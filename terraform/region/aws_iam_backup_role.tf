resource "aws_iam_role" "aurora_backup_role" {
  name               = "aurora_cluster_backup_role"
  assume_role_policy = data.aws_iam_policy_document.aurora_cluster_backup_role.json
}

resource "aws_iam_role_policy_attachment" "aurora_backup_role" {
  role       = aws_iam_role.aurora_backup_role.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSBackupServiceRolePolicyForBackup"
}

data "aws_iam_policy_document" "aurora_cluster_backup_role" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["backup.amazonaws.com"]
    }
  }
}
