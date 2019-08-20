#!/bin/bash

pip3 install -r lambda/requirements.txt  --target ./lambda/vendor
cd ./lambda; zip -r9 /tmp/lambda_function_payload.zip .; cd ..
rm -r ./lambda/vendor
