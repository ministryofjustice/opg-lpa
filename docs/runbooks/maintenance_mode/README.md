# Manage Maintenance Mode

This script will enable or disable maintenance mode for a targeted environment.

## Setup

### Start a Cloud9 Instance

Set up and configure a [Cloud9 instance](../cloud9/README.md)

### Get script and run it

Git clone the opg-lpa repository.

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
