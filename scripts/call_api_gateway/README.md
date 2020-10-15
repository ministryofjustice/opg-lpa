# call api gateway

This script returns case data for a Sirius case ID.

It makes an authenticated request to the sirius api gateway.

The script uses your IAM user credentials to assume the appropriate role.
By default will query the development Sirius API Gateway.
The `--production` flag can be used to request a result from the production Sirius API Gateway.

You can provide the script credentials using aws-vault [aws-vault](https://github.com/99designs/aws-vault)

Call the development gateway

```bash
aws-vault exec identity -- python ./call_api_gateway.py A12345678
```

Call the production gateway

```bash
aws-vault exec identity -- python ./call_api_gateway.py A12345678 --production
```
