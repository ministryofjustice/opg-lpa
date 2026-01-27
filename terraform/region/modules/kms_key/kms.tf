resource "aws_kms_key" "eu_west_1" {
  description             = var.description
  deletion_window_in_days = 7
  enable_key_rotation     = true
  policy                  = data.aws_iam_policy_document.kms_key.json
  multi_region            = true
  provider                = aws.eu_west_1
}

resource "aws_kms_replica_key" "eu_west_2" {
  description             = var.description
  deletion_window_in_days = 7
  primary_key_arn         = aws_kms_key.eu_west_1.arn
  policy                  = data.aws_iam_policy_document.kms_key.json
  provider                = aws.eu_west_2
}

resource "aws_kms_key" "backup" {
  description             = var.description
  provider                = aws.backup
  deletion_window_in_days = 7
  policy                  = data.aws_iam_policy_document.kms_key.json
}
resource "aws_kms_alias" "eu_west_1" {
  name          = "alias/${var.alias}"
  target_key_id = aws_kms_key.eu_west_1.key_id
  provider      = aws.eu_west_1
}

resource "aws_kms_alias" "eu_west_2" {
  name          = "alias/${var.alias}"
  target_key_id = aws_kms_replica_key.eu_west_2.key_id
  provider      = aws.eu_west_2
}

resource "aws_kms_alias" "backup" {
  name          = "alias/${var.alias}"
  target_key_id = aws_kms_key.backup.key_id
  provider      = aws.backup
}
