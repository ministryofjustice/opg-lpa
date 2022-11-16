# Using cloud9 to access RDS instance

Generally, if you want to access an RDS instance, om AWS you will need to spin up a Cloud9 instance.
The instructions below help you to do that, so you can query data and perform database operations.

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

it is recommended that you clone the repo into the Cloud9 instance:

``` bash
git clone https://github.com/ministryofjustice/opg-lpa.git
```

go to the folder where the cloud9 script is now sitting:

``` bash
cd ~/environment/opg-lpa/docs/runbooks/cloud9
```

Execute the script below, passing it the name of the environment you want to connect to. This matches the terraform workspace name for the environment, e.g. the prefix for the url, and is mentioned CircleCI Build. **Note** the **`.`** in the command below.

``` bash
 . cloud9_init.sh 114-postmigra
```

You should see tools being installed and RDS details being output, to show how to connect.

## Using the environment

### Connecting to PostgreSQL

If you have run the setup script correctly then you can use psql to connect to the databases for that environment.

``` bash
psql api2
```

### Cleanup

Once you've finished with your environment go to the Cloud 9 Dashboard in AWS Console and delete it.
