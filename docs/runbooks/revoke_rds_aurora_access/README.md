# Revoke access to Aurora cluster

We can restrict access to at-risk Aurora clusters by removing network access to the instances.

The script in this folder removes security group rules for the Aurora cluster and the RDS proxy.


## Prerequisites

- Install `uv` and run `uv sync` from this directory. Run the commands from this directory.
- Set up aws-vault using [aws-vault-assumable-roles](../aws-vault-assumable-roles.md), with the chosen profile assuming `role/breakglass` in the bucket's account.
- Verify the environments to revoke access to.

## Using the script

Define one or more environments with `--environment_names`.

```shell
aws-vault exec moj-lpa-prod -- uv run python ./main.py  --region 'eu-west-1' --environment_names 'production'
```

Multiple environments can be passed like this:

```shell
aws-vault exec moj-lpa-dev -- uv run python ./main.py  --region 'eu-west-1' --environment_names 'test' '<second-environment>'
```

## Returning to original configuration

Access can be restored by applying Terraform locally in the relevant folder using the breakglass role.
