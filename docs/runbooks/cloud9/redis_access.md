# Using Cloud9 to access Redis

## Overview

Generally, if you want to access a Redis instance, on AWS you will need to spin up a Cloud9 instance. The instructions below help you to do that, so you can query data and perform database operations.

## Setup

### Starting a Cloud 9 Instance

1. Log into the AWS Console
2. Ensure you are in the Ireland(eu-west-1) region
3. Switch to the appropriate role, in the appropriate account
4. Search for the Cloud 9 Service in the list of Services
5. Once on the Cloud 9 Dashboard select "Create Environment"
    1. Name the cloud 9 instance whatever you like, but prefixed with the associated ticket number
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

Copy the redis_init.py script to the Cloud9 instance:

``` bash
wget https://raw.githubusercontent.com/ministryofjustice/opg-lpa/main/docs/runbooks/cloud9/redis_init.py
```

Execute the script and you should see the redis details being output, to show how to connect.

``` bash
python redis_init.py
redis-cli --tls -p 6379 -h <endpoint>
```

Once you've finished, run `redis_init.py cleanup` to cleanup the security groups then go to Cloud 9 Dashboard in AWS Console and delete the instance.
