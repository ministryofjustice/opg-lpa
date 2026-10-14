# Shutting Down Compute in ECS

## Prerequisites

You will need the aws cli tool installed.

```bash
brew install awscli
```

## Scale ECS compute to zero

Once maintenance mode has been enabled, compute can be scaled down to zero via 2 possible methods.

### Option 1: via the CLI (recommended)

Sets the desired task count to zero for all services.

**Note:** Omit `mock-onelogin` for production.

```bash
for service in api admin pdf front mock_onelogin; do
  aws-vault exec <profile> -- aws ecs update-service \
    --cluster <environment>-online-lpa \
    --service $service \
    --desired-count 0; done
```

Check that every service's running and desired count is `0`:

```bash
aws-vault exec <profile> -- aws ecs describe-services \
  --cluster <environment>-online-lpa \
  --services api admin pdf front mock_onelogin \
  --query 'services[].{name:serviceName,desired:desiredCount,running:runningCount}'
```

### Option 2: via Terraform

`mock_onelogin` (non-production only) is not driven by autoscaling and has no
terraform-managed way to scale to zero; use the Option 1 CLI command for it.

1. Edit [terraform.tfvars.json](../../../terraform/environment/terraform.tfvars.json) and set
`minimum` and `maximum` to `0` for every service under
`environments.<environment>.autoscaling`: `front`, `api`, `pdf`, `admin`, `mock_onelogin`.

2. Navigate to `terraform/environment/` and select the environment's workspace:

  ```bash
  aws-vault exec identity -- terraform workspace select <environment>
  ```

3. For `production`, open `terraform/environment/.envrc`, change
  `TF_VAR_default_role=operator` to `TF_VAR_default_role=breakglass`, and run
  `direnv allow` from that directory.
4. Plan the terraform and carefully inspect the plan before applying:

  ```bash
  aws-vault exec identity -- terraform plan
  ```

  ```bash
  aws-vault exec identity -- terraform apply
  ```

Alternatively, commit the change on a branch, open a pull request and let the pipeline
handle the deploy.

### Reverting

1. If Option 1 (CLI) was used, set each service's desired count back to its
   `minimum` value for that environment under `environments.<environment>.autoscaling` in
   [terraform.tfvars.json](../../../terraform/environment/terraform.tfvars.json)
   (`mock-onelogin-service` is `1` if `mock_onelogin_enabled` is `true`)

   ```bash
   aws-vault exec <profile> -- aws ecs update-service --cluster <environment>-online-lpa --service <service-name> --desired-count <minimum>
   ```

   Run this once per service, substituting `<service-name>` and its `<minimum>`.
2. If Option 2 (Terraform) was used, revert the `terraform.tfvars.json` change and apply
  locally or via the pipeline.
3. Follow the maintenance mode usage above to turn off maintenance mode.
