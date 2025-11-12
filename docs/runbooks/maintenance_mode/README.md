# Manage Maintenance Mode

This script will enable or disable maintenance mode for a targeted environment.
This can be run locally or via a cloud9 instance, dependant on needs.

## Setup - Local Credentials Route

Use this route if you have the required access via aws-vault to make changes to the environment that needs to be put into maintenenance mode.

You will need to set up assumable roles in aws-vault. Follow the instructions at [setting up credentials](../setting_up_aws_credentials/setting_up_credentials.md)

### Usage

To turn maintenance mode on (e.g. in preproduction):

```sh
aws-vault exec moj-lpa-preprod -- ./manage_maintenance.sh \
  --environment preproduction \
  --maintenance_mode
```

to turn maintenance mode off:

```sh
aws-vault exec moj-lpa-preprod -- ./manage_maintenance.sh \
  --environment preproduction \
  --disable_maintenance_mode
```

## Setup - Remote Route

If you don't have this, you can also set up cloud9 on the relevant account in the AWS console.

### Start a Cloud9 Instance

Set up and configure a [Cloud9 instance and clone the repo](../cloud9/README.md)

### Usage

Set maintenance_mode to True to turn maintenance on

``` bash
cd ~/environment/opg-lpa/docs/runbooks/maintenance_mode
./manage_maintenance.sh \
  --environment preproduction \
  --maintenance_mode
```

Set maintenance_mode to False to turn maintenance off

``` bash
cd ~/environment/opg-lpa/docs/runbooks/maintenance_mode
./manage_maintenance.sh \
  --environment preproduction \
  --disable_maintenance_mode
```

### Note

If you get a permission related error trying to run this script you may require the `breakglass` to do so. In this case, please contact your webops team member or the community channel.
