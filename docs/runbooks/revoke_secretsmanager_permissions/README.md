# Revoke access to Secrets Manager secrets with a resource policy

We can restrict access to secrets using a resource policy that denies access.

The script in this folder replaces the existing secret resource policy with a Deny policy.

## Deny Policy

This policy denies all Secrets Manager actions on the secret to all principals except the account's `breakglass` role, which retains its existing access.

The breakglass role can replace or remove the Deny policy. The root principal can also recover the policy.

## Prerequisites

- Install `uv` and run `uv sync` from this directory. Run the commands from this directory.
- Set up aws-vault using [aws-vault-assumable-roles](../aws-vault-assumable-roles.md), with the chosen profile assuming `role/breakglass` in the secret's account.
- Verify the full secret ARNs and region. Secrets Manager is regional; use a separate run for each region.

## Using the script

Define one or more full secret ARNs with `--secret_arns` and revoke access with the required `--revoke` flag, putting the Deny policy. The script defaults to `eu-west-1`; specify `--region` when targeting secrets in another region.

```shell
aws-vault exec ual-prod -- uv run python ./main.py --secret_arns 'arn:aws:secretsmanager:eu-west-1:690083044361:secret:gov-uk-onelogin-client-id-<suffix>' --revoke
```

Multiple secrets in the same region can be passed like this:

```shell
aws-vault exec ual-prod -- uv run python ./main.py --secret_arns 'arn:aws:secretsmanager:eu-west-1:690083044361:secret:gov-uk-onelogin-client-id-<suffix>' 'arn:aws:secretsmanager:eu-west-1:690083044361:secret:lpa-data-store-secret-<suffix>' --revoke
```

## Returning to original configuration

If there were existing resource policies on the secret that need to be restored, this can be done by applying Terraform locally in the relevant folder using the breakglass role.
