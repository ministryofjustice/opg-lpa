resource "aws_kms_key" "eu_west_1" {
  description             = var.description
  deletion_window_in_days = var.deletion_window
  enable_key_rotation     = true
  policy                  = data.aws_iam_policy_document.kms_key.json
  multi_region            = true
  region                  = "eu-west-1"
}

resource "aws_kms_replica_key" "eu_west_2" {
  description             = var.description
  deletion_window_in_days = var.deletion_window
  primary_key_arn         = aws_kms_key.eu_west_1.arn
  policy                  = data.aws_iam_policy_document.kms_key.json
  region                  = "eu-west-2"
}

resource "aws_kms_alias" "eu_west_1" {
  name          = "alias/${var.alias}"
  target_key_id = aws_kms_key.eu_west_1.key_id
  region        = "eu-west-1"
}

resource "aws_kms_alias" "eu_west_2" {
  name          = "alias/${var.alias}"
  target_key_id = aws_kms_replica_key.eu_west_2.key_id
  region        = "eu-west-2"
}
