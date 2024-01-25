#!/bin/sh

echo Starting local config

DYNAMODN_ENDPOINT=http://${AWS_ENDPOINT_DYNAMODB}
/usr/local/bin/waitforit -address=tcp://${AWS_ENDPOINT_DYNAMODB} -timeout 60 -retry 6000 -debug

DEFAULT_REGION=eu-west-1

# ----------------------------------------------------------
# Add any setup here that is performed with Terraform in AWS.

aws dynamodb create-table \
--attribute-definitions="AttributeName=id,AttributeType=S" \
--table-name=Sessions \
--key-schema="AttributeName=id,KeyType=HASH" \
--provisioned-throughput="ReadCapacityUnits=100,WriteCapacityUnits=100" \
--region=${DEFAULT_REGION} \
--endpoint=$DYNAMODN_ENDPOINT

aws dynamodb create-table \
--attribute-definitions="AttributeName=id,AttributeType=S" \
--table-name=Locks \
--key-schema="AttributeName=id,KeyType=HASH" \
--provisioned-throughput="ReadCapacityUnits=100,WriteCapacityUnits=100" \
--region=${DEFAULT_REGION} \
--endpoint=$DYNAMODN_ENDPOINT

aws dynamodb create-table \
--attribute-definitions="AttributeName=id,AttributeType=S" \
--table-name=Properties \
--key-schema="AttributeName=id,KeyType=HASH" \
--provisioned-throughput="ReadCapacityUnits=100,WriteCapacityUnits=100" \
--region=${DEFAULT_REGION} \
--endpoint=$DYNAMODN_ENDPOINT

# ----------------------------------------------------------
/usr/local/bin/waitforit -address=tcp://${OPG_LPA_COMMON_SQS_ENDPOINT} -timeout 60 -retry 6000 -debug

ATTR="MessageRetentionPeriod=3600,\
FifoQueue=true,\
ContentBasedDeduplication=true,\
VisibilityTimeout=90"

aws sqs create-queue \
--queue-name=${OPG_LPA_COMMON_PDF_QUEUE_NAME} \
--attributes="${ATTR}" \
--region=${DEFAULT_REGION} \
--endpoint=http://${OPG_LPA_COMMON_SQS_ENDPOINT}

# ----------------------------------------------------------
/usr/local/bin/waitforit -address=tcp://${OPG_LPA_COMMON_S3_ENDPOINT} -timeout 60 -retry 6000 -debug

aws s3api create-bucket \
--endpoint=http://${OPG_LPA_COMMON_S3_ENDPOINT} \
--region=${DEFAULT_REGION} \
--bucket=${OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET} \
--create-bucket-configuration LocationConstraint=eu-west-1
