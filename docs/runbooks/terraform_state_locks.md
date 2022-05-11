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





