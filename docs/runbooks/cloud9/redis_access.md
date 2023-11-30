# Using Cloud9 to access Redis

## Overview

Generally, if you want to access a Redis instance, on AWS you will need to spin up a Cloud9 instance. The instructions below help you to do that, so you can query data and perform database operations.

## Setup

### Starting a Cloud9 Instance

1. Log into the AWS Console
2. Ensure you are in the correct region. Normally this will be eu-west-1 (Ireland)
3. Switch to the appropriate role, in the appropriate account for the environment you are working on
4. Search for the Cloud9 Service in the list of AWS Services and select it
5. Once on the Cloud9 Dashboard select "Create Environment"
    1. Name the cloud9 instance whatever you like, but prefixed with the associated ticket number
    2. eg LPA-1234-my-work-cloud9-instance
    3. optionally give a description
6. Leave all defaults for **environment type, instance type** and **platform**.
7. Adjust the **Cost-saving setting** to suit your needs, basing this on how long you will need the instance.
8. under the **Network settings (advanced)** drop tab:
    1. Change **Connection** to Secure Shell (SSH)
    2. Leave the **Network (VPC)** dropdown as is.
    3. select a public facing **Subnet** from the dropdown. **Note** these are usually the shorter named groups.
    4. Failure to do this will mean your instance cannot be accessed and will fail to deploy


### Once Connected

Open a terminal window in your nearly created Cloud9 instance and run the following command to connect to the Redis cluster.

``` bash
wget https://raw.githubusercontent.com/ministryofjustice/opg-lpa/main/docs/runbooks/cloud9/redis_init.py
```

Execute the script inside of your Cloud9 terminal and you should see the URL of each Redis instance in the account and region which you can now connect to using the `redis-cli` tool. You must use TLS to connect to the Redis instance, so you will need to use the `--tls` flag.

``` bash
python redis_init.py
redis-cli --tls -p 6379 -h <endpoint>
```

Once you've finished, run `redis_init.py cleanup` to cleanup the security groups then go to Cloud9 Dashboard in AWS Console and delete the instance.
