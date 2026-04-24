# Using Cloudshell to access PostgresSQL

## Overview

If you want to access a the Postgres database on AWS you will need to start a Cloudshell VPC environment. The instructions below help you to do that, so you can query data and perform database operations.

Cloudshell VPC environments have no persistent storage (data in $HOME is deleted with the VPC environment) and no access to the internet but do come with preinstalled tools.

See [aws cloudshell documentation](https://docs.aws.amazon.com/cloudshell/latest/userguide/welcome.html) for more information.

## Setup

### Starting a Cloudshell Environment

1. Log into the AWS Console
2. Switch to the appropriate role (breakglass or data-access), in the appropriate account for the environment you are working on
3. Ensure you are in the correct region. Normally this will be eu-west-1 (Ireland)
4. Search for the Cloudshell in the list of AWS Services and select it
5. Select **Create a VPC environment**
    1. Name the Cloushell environment whatever you like, but prefixed with the associated ticket number
    2. eg LPA-1234-my-work
    3. Select the VPC that starts with the name **OnlineLPAService**
    4. Select an **application** subnet. There are 3 to choose from.
    5. Select the security group that has the description **Security group for Cloudshell**
    6. Click **Create**

#### Postgres

Once the environment has been created you can use the preinstalled tools to connect to the database.

Identify the correct instance to connect to by describing the cluster and use a `DBInstanceIdentifier` that meets your needs. Prefer using instances with `"IsClusterWriter": false`.

```bash
aws rds describe-db-clusters --db-cluster-identifier api2-20260415-production-cluster | jq -r '.DBClusters[0].DBClusterMembers'
```

Then provide the `DBInstanceIdentifier` to the commands below.

```bash
export DBInstanceIdentifier="api2-20260415-production-1"
export RDSHOST=$(aws rds describe-db-instances --db-instance-identifier $DBInstanceIdentifier | jq -r '.DBInstances[0].Endpoint.Address')
export RDSPASS=$(aws secretsmanager get-secret-value --secret-id production/api_rds_credentials | jq ."SecretString" -r | jq ."password" -r)
psql "host=$RDSHOST port=5432 dbname=api2 user=opglpaapi password=$RDSPASS"
```

when finished, exit psql with `\q`. The environment will automatically be deleted after 20-30 minutes of inactivity.

To manually delete the environment.

1. Select **Actions** in the top right
2. Select **Delete**, and conform the deletion
