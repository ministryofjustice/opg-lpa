# Terraform State Locks

Terraform utilises a locking mechanism on remote state files for write based operations, ensuring singular access, avoiding possible curruption and is handled automatically by the cli tooling.

Occasionally a state file is left locked, this is generally from a terraform related command not exiting cleanly. This will then cause all other terraform operations using that state file to fail.

We seperate out our terraform between account, region & environment levels and also have a seperate workspace (development, preproduction etc) we limit the impact of the state lock - so a lock in development would not impact the ability to deploy production.

## Unlocking State

Unlocking a state file can be a risky action, accidential deletions are possible, so please be cautious, in particular if this is production.

State unlocking is one of the operations that should be handled on a local machine, rather than via pipline process.

For production, you are likely to require `breakglass` permission to run a state lock due to the risks involved.

### Which state needs unlocking?

Our terraform is split by region, account and environment levels, each with their own state file. The pipeline step that failed will be named to match. When you know this, you can change to the matching directory on your local cli.

### Terraform version

Please ensure you are running the same (or compatible) version of `terraform` as the pipeline to avoid conflicts. We tend to utilise `tfswitch` or `asdf` to align our local versions.

If you are unsure which version you need to use, look for a variable named `required_version` in the terraform for check the pipline configuration.

### Terraform workspace

Within the local `.envrc` file there will be a commented out variable called `TF_Workspace`, this defaults to `development`, but if you are looking at a state lock for non-dev environments, you will need to change this accordingly.

You can check which is in use within the pipeline or by using terraform itself (`terraform workspace list`).

### Direnv & init

When you have the workspace variable set, you can now run `direnv allow` within the directory to set envirmonent variables for you.

After that has been run, you can now initialise terraform:

```aws-vault exec identity -- terraform init```

You may have part of this command aliased to `tf`

### Getting the lock id

When terraform command finds a locked state file it will exit and provide a summary of the error, including the lock id - which is used in unlocking.

This will be displayed under a section called `Lock Info` and a key of `ID`. Copy that, as we need to use it in the next command.

### Running the unlock

From your configured terminal run the following:

```aws-vault exec identity -- terraform force-unlock ${LOCK_ID}```

Replacing the `${LOCK_ID}` with the details from the above step.

The command will then ask you to confirm by typing `yes` and when complete will output a message:

```Terraform state has been successfully unlocked```

### Step-by-step instructions for unblocking builds

The above steps adapt to multiple scenarios where you may want to remove a lock. However, for most purposes, we only do this when builds are blocked due to `pr_build` CircleCI pipeline runs which fail because they can't get a terraform state lock.

This happens in the `dev_region_apply_terraform` step, giving us an error like this in the logs:

```
Acquiring state lock. This may take a few moments...
╷
│ Error: Error acquiring the state lock
│
│ Error message: ConditionalCheckFailedException: The conditional request failed
│ Lock Info:
│   ID:        ef61c6bd-4030-3098-25d3-28f971e18376
│   Path:      opg.terraform.state/env:/development/moj-lasting-power-of-attorney-region/terraform.tfstate
│   Operation: OperationTypeApply
│   Who:       root@6dbbe46abe12
│   Version:   1.1.2
│   Created:   2022-05-11 10:09:38.706622546 +0000 UTC
│   Info:
│
│
│ Terraform acquires a state lock to protect the state from being written
│ by multiple users at the same time. Please resolve the issue above and try
│ again. For most commands, you can disable locking with the "-lock=false"
│ flag, but this is not recommended.
```

The build fails as terraform (running in the pipeline) can't get a state lock (see above) to enable it to deploy the new dev infrastructure.

**Important: try not to start a new AWS session inside an existing one. Open a new console before following the instructions below.**

#### Region state lock

The state lock for a region can be removed via your local opg-lpa checkout as follows:

1. Make sure there are no running builds for your PR; if there are, wait for them to fail or finish, or cancel them.
2. `cd terraform/region`
3. `source .envrc` (you're supposed to be able to use direnv to do this, but I can't get it to work properly; source works just as well)
4. `aws-vault exec identity -- terraform init`; this downloads plugins/dependencies needed to run terraform
5. Get the lock ID from the error message in CircleCI; in the above log example, it is `ef61c6bd-4030-3098-25d3-28f971e18376`
6. `aws-vault exec identity -- terraform force-unlock <lock ID>`, where `<lock ID>` is the lock ID derived in step 4

If this works, you should see something like this:

```
Do you really want to force-unlock?
  Terraform will remove the lock on the remote state.
  This will allow local Terraform commands to modify this state, even though it
  may be still be in use. Only 'yes' will be accepted to confirm.

  Enter a value: yes
```

When prompted to "Enter a value", type `yes` and press *Return*.

If this is successful, you should see:

```
Terraform state has been successfully unlocked!

The state has been unlocked, and Terraform commands should now be able to
obtain a new lock on the remote state.
```

#### Environment state lock

The state lock for an environment can be removed via your local opg-lpa checkout with instructions similar to those for a region state lock:

1. Make sure there are no running builds for your PR; if there are, wait for them to fail or finish, or cancel them.
2. `cd terraform/environment`
3. `source .envrc`
4. `aws-vault exec identity -- terraform init`
5. Get the lock ID from the error message in CircleCI; in the above log example, it is `ef61c6bd-4030-3098-25d3-28f971e18376`
6. An extra step is required to get into the correct terraform workspace. To do this, you need to know the identifier for the PR, which is shown after the title of the PR in github.
7. List the workspaces for the environment with `aws-vault exec identity -- terraform workspace list | grep <PR ID>`. This will give you a workspace something like `1350lpal115`.
8. `export TF_WORKSPACE=1350lpal115`, replacing `1350lpal115` with your workspace ID.
9. `aws-vault exec identity -- terraform force-unlock <lock ID>`, where `<lock ID>` is the lock ID derived in step 4

This should show you the force-unlock prompts as appear for the region state unlock.
