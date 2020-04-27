# Using cloud9

## Setup

### Starting a Cloud 9 Instance

Log into the AWS Console
Ensure you are in the Ireland(eu-west-1) region
Switch to the appropriate role, in the appropriate account
Search for the Cloud 9 Service in the list of Services
Once on the Cloud 9 Dashboard select "Create Environment"
Create the environment leaving all the default values, name it whatever you like

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
