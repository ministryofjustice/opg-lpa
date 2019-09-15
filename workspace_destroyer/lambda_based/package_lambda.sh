#!/bin/bash

pip3 install -r lambda/requirements.txt  --target ./lambda/vendor
cd ../lambda; zip -r9 /tmp/lambda_function_payload.zip .; cd ..
rm -r ../lambda/vendor



#!/bin/bash

pip3 install -r ./workspace_destroyer_lambda/lambda_function_destroyer/requirements.txt  --target ./workspace_destroyer_lambda/lambda_function_destroyer/vendor
cd lambda_function_destroyer; zip -r9 /tmp/lambda_function_payload.zip .; cd ..
rm -r ../workspace_destroyer_lambda/lambda_function_destroyer/vendor
