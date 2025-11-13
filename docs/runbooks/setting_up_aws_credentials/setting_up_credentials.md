# Setting up AWS credentials

The instructions below support a number of AWS actions run from the command line, in order to help with your local development environment.

## Requirements

You will need the aws-cli tool, which can be installed with homebrew.

``` bash
brew install awscli
```

You also will need to have a working installation of aws-vault, configured with an identity profile.
instructions on how to install are in the [OPG New Starter Guide](https://ministryofjustice.github.io/opg-new-starter/amazon.html#aws-vault>),

## Setting up credentials

any aws command line work will be performed with `aws-cli`.
To assume the correct IAM role to perform the redeploy, we will add new profiles to the aws configuration stored in `~/.aws/config`.

Copy the following into your AWS config found at `~/.aws/config`.
Replace `<aws-username>` with your AWS user name.

``` config
[profile moj-lpa-dev]
region=eu-west-1
role_arn=arn:aws:iam::050256574573:role/operator
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<aws-username>

[profile moj-lpa-preprod]
region=eu-west-1
role_arn=arn:aws:iam::987830934591:role/breakglass
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<aws-username>

[profile moj-lpa-prod]
region=eu-west-1
role_arn=arn:aws:iam::980242665824:role/breakglass
source_profile=identity
mfa_serial=arn:aws:iam::631181914621:mfa/<aws-username>
```

note the above list is not exhaustive, and from time to time may be added to or have profiles removed.

Once added you will be able to see the new profiles available for use in aws-vault.

``` bash
aws-vault list
```
