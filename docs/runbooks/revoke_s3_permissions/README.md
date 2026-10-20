# Revoke access to S3 buckets with a bucket policy

We can restrict access to at-risk S3 buckets using a bucket policy that denies access.

The script in this folder replaces the existing bucket policy with a Deny policy.

## Deny Policy

This policy denies all S3 actions on the bucket and its objects to all principals except the account's `breakglass` role, which retains its existing access.

The breakglass role can replace or remove the Deny policy. The root principal can also recover the policy.

## Prerequisites

- Install `uv` and run `uv sync` from this directory. Run the commands from this directory.
- Set up aws-vault using [aws-vault-assumable-roles](../aws-vault-assumable-roles.md), with the chosen profile assuming `role/breakglass` in the bucket's account.
- Verify the bucket names to revoke access to.

## Using the script

Define one or more bucket names with `--bucket_names` and revoke access with the required `--revoke` flag, putting the Deny policy.

```shell
aws-vault exec moj-lpa-prod -- uv run python ./main.py --bucket_names 'use-a-lpa-dynamodb-exports-production' --revoke
```

Multiple buckets can be passed like this:

```shell
aws-vault exec moj-lpa-prod -- uv run python ./main.py --bucket_names 'use-a-lpa-dynamodb-exports-production' '<second-bucket>' --revoke
```

## Returning to original configuration

If there were existing resource policies on the bucket that need to be restored, this can be done by applying Terraform locally in the relevant folder using the breakglass role.
