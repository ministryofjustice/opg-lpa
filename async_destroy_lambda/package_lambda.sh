#!/bin/bash

pip3 install -r lambda/requirements.txt  --target ./lambda/vendor
cd ./lambda; zip -r9 ../../terraform/terraform_account/lambda_function_payload.zip .; cd ..
rm -r ./lambda/vendor
