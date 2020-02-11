# Replacing secrets on a running environment

Secrets for the product are stored in AWS Secrets Manager and provided to ECS tasks at task launch.

Secrets are stored per account.

To replace a secret on an environment, update the secret value then force a redeploy on the ecs service to deploy the new secret value.

These instructions show how to do that.

## Requirements

You will need aws-vault installed. A profile for the appropriate account and role needs to be set up in order to be able to execute the commands below.

See https://ministryofjustice.github.io/opg-new-starter/amazon.html#using-aws-vault-with-your-account-in-opg-identity for information on how to install and configure aws-vault.

### Set up role

Create a new profile for aws-vault by manually adding it to your aws config file

```
vi ~/.aws/config
```

Add the following block, supplying the account id and role you wish to assume

```
[profile <PROFILE_NAME>]
region=eu-west-1
role_arn=arn:aws:iam::<ACCOUNT_ID>:role/<ROLE>
source_profile=identity
mfa_serial=arn:aws:iam::<IDENTITY_ACCOUNT_ID>:mfa/<USER.NAME>
```

Once this is done you will be able to see your new profile available for use
```
aws-vault list
```

## Replace a secret in AWS Secrets Manager
```
aws-vault exec <PROFILE_NAME> -- aws secretsmanager put-secret-value --secret-id path_to/secret --secret-string 'value'
```


## Force a redeploy of AWS ECS Services
Run the following commands for each of the following services, where a secret needs to be replaced;

- front
- api
- pdf
- admin


Check current status of service
```
aws-vault exec <PROFILE_NAME> -- aws ecs describe-services --cluster <CLUSTER_NAME> --services <SERVICE_NAME>
```
Force a redeployment of service
```
aws-vault exec <PROFILE_NAME> -- aws ecs update-service --cluster <CLUSTER_NAME> --force-new-deployment --service <SERVICE_NAME>
```


You can now track the status of the redeployment with thee describe clusters command.
The running task count and the pending task count will be changing, scaling up and then returning to normal running count.
```
aws-vault exec <PROFILE_NAME> -- aws ecs describe-clusters --clusters <CLUSTER_NAME>
```

After the redeploy has finished the new secret value will be in use.
