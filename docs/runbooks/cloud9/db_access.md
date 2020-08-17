# Using cloud9

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
8 under the **Network settings (advanced)** drop tab:
    1. Leave the **Network (VPC)** dropdown as is.
    2. select a public facing **Subnet** from the dropdown.
    3. failure to do this will mean your instance cannot be accessed and will fail to deploy


### Once Connected

In the terminal session create a script called cloud9_init.sh and paste in the contents of cloud9_init.sh
Give the script execution permissions with

``` bash
chmod +x cloud9_init.sh
```

Execute the script, passing it the name of the environment you want to connect to, (matches the terraform workspace name for the environment)

``` bash
. cloud9_init.sh 114-postmigra
```

You should see tools being installed and an RDS & Elasticsearch connection string output

## Using the environment

### Connecting to PostgreSQL

If you have run the setup script correctly then you can use psql to connect to the databases for that environment.

``` bash
psql postgres
```

### Cleanup

Once you've finished with your environment go to the Cloud 9 Dashboard and delete your environment.
