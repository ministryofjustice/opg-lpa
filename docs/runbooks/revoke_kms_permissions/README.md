# Revoke access to KMS keys with a key policy

We can restrict access to a KMS key using a key policy that denies kms actions.

The script replaces the existing key policy with a temporary Deny policy.

## Deny Policy

The Deny statement blocks all KMS actions for every principal, except the account's `breakglass` role. A separate Allow statement explicitly grants breakglass `kms:*` so it can administer the key and restore the original key policy.

Other principals and AWS services lose KMS access while this policy is present. The original key-policy statements are removed and must be restored by Terraform.

KMS key policies are regional. DynamoDB and S3 may cache data keys, so denying KMS access may not immediately block access to already-decrypted data.

## Prerequisites

- Install `uv` and run `uv sync` from this directory. Run the commands from this directory.
- Set up aws-vault using [aws-vault-assumable-roles](../aws_vault_assumable_roles/aws_vault_assumable_roles.md), with the chosen profile assuming `role/breakglass` in the key's account.
- Verify the key ARNs and regions. For multi-Region keys, run once for each regional key ARN.
- Restore the original key policies by applying Terraform locally in the correct folder with breakglass.

## Using the script

Define the specific key ARN or ARNs with `--key_arns` and revoke access with the required `--revoke` flag. Only the listed keys are changed, and keys passed in one run must be in the selected region.

```shell
aws-vault exec moj-lpa-prod -- uv run python ./main.py --key_arns '<key-arn>' --region 'eu-west-1' --revoke
```

Multiple keys in the same region can be passed like this:

```shell
aws-vault exec moj-lpa-prod -- uv run python ./main.py --key_arns '<key-arn>' '<another-key-arn>' --region 'eu-west-1' --revoke
```

## Returning to original configuration

Apply Terraform locally in the correct folder using breakglass to restore the original key policies.
