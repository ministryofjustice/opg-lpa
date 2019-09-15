#! /bin/bash

cd app
pip3 install -r requirements.txt --target ./package
zip -r9 ./lambda_function_payload.zip .
