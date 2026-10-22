# Revoke access to a DynamoDB table with a resource policy

We can restrict access to at-risk DynamoDB tables using a resource policy that denies specified actions.

The script in this folder replaces the existing resource policy with a Deny policy, or an Allow policy.

## Deny Policy

This policy denies the listed DynamoDB actions to all principals and allows the breakglass role to call `dynamodb:PutResourcePolicy`.

The breakglass role can use this action to replace the Deny policy with the Allow policy and restore access without using root credentials. Root credentials can also call `PutResourcePolicy` regardless of the resource policy attached to a DynamoDB table.

We can combine use of this policy, with stricter policies on the breakglass role, limiting it to specific users

## Allow Policy

This policy grants the account's breakglass role permission to all DynamoDB actions. This restores console access to the table, and helps with running terraform for example to restore normal configuration to a DynamoDB table.

## Prerequisites

- Install `uv` and run `uv sync` from this directory.
- You will need to set up assumable roles in aws-vault. Follow the instructions at [aws-vault-assumable-roles](../aws-vault-assumable-roles.md).
- Verify the table ARNs and region to revoke access to.

## Using the script

define one or more table ARNs with `--table_arns` and revoke access with `--revoke`, putting the Deny policy

```shell
aws-vault exec moj-lpa-prod -- uv run python ./main.py --table_arns '<dynamodb-table-arn>' --revoke
```

Multiple tables can be passed like this:

```shell
aws-vault exec moj-lpa-prod -- uv run python ./main.py --table_arns '<dynamodb-table-arn>' '<another-dynamodb-table-arn>' --revoke
```

Without the `--revoke` flag, the script will put the Allow policy

```shell
aws-vault exec moj-lpa-prod -- uv run python ./main.py --table_arns '<dynamodb-table-arn>'
```

The script defaults to the `eu-west-1` region. Specify `--region` when targeting a table in another region.

## Returning to original configuration

At the time of writing, the DynamoDB tables have no existing resource policies. If a table has a policy that needs to be restored, run Terraform locally using the breakglass role to return it to its original state.
