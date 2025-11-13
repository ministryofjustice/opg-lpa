# Replacing secrets on a running environment

Secrets for the product are stored in AWS Secrets Manager and provided to ECS tasks at task launch.

Secrets are stored per account.

To replace a secret on an environment, update the secret value then force a redeploy on the ecs service to deploy the new secret value.

These instructions show how to do that.

## Requirements

please see [Setting up AWS Credentials](setting_up_aws_credentials/setting_up_credentials.md) for details of how to set up aws cli and aws-vault on your local machine.

## Replace a secret in AWS Secrets Manager

```bash
aws-vault exec <PROFILE_NAME> -- aws secretsmanager put-secret-value --secret-id path_to/secret --secret-string 'value'
```

## Force a redeploy of AWS ECS Services

Run the following commands for each of the following services, where a secret needs to be replaced;

- front
- api
- pdf
- admin

Check current status of service

``` bash
aws-vault exec <PROFILE_NAME> -- aws ecs describe-services --cluster <CLUSTER_NAME> --services <SERVICE_NAME>
```

Force a redeployment of service

``` bash
aws-vault exec <PROFILE_NAME> -- aws ecs update-service --cluster <CLUSTER_NAME> --force-new-deployment --service <SERVICE_NAME>
```

You can now track the status of the redeployment with thee describe clusters command.
The running task count and the pending task count will be changing, scaling up and then returning to normal running count.

``` bash
aws-vault exec <PROFILE_NAME> -- aws ecs describe-clusters --clusters <CLUSTER_NAME>
```

After the redeploy has finished the new secret value will be in use.
