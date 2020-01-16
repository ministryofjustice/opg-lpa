# call api gateway

This script returns case data for a Sirius case ID.

It makes an authenticated request to the sirius api gateway. 

The script reads account id, iam role and api gateway url from environment variables.

You can set these using direnv
```bash
direnv allow
```
 or by sourcing the .envrc file
```bash
source .envrc
```

The script uses your IAM user credentials to assume the appropriate role.

You can provide the script credentials using aws-vault

```bash
aws-vault exec identity -- python ./call_api_gateway.py A12345678
```
