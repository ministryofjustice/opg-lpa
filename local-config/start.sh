#!/bin/sh

echo Starting local config

DYNAMODN_ENDPOINT=http://${AWS_ENDPOINT_DYNAMODB}
/usr/local/bin/waitforit -address=tcp://${AWS_ENDPOINT_DYNAMODB} -timeout 60 -retry 6000 -debug

DEFAULT_REGION=eu-west-1
PERFPLAT_LAMBDA_NAME=perfplatworker

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
--attributes="$ATTR" \
--region=${DEFAULT_REGION} \
--endpoint=http://${OPG_LPA_COMMON_SQS_ENDPOINT}

# ----------------------------------------------------------
/usr/local/bin/waitforit -address=tcp://${OPG_LPA_COMMON_S3_ENDPOINT} -timeout 60 -retry 6000 -debug

aws s3api create-bucket \
--endpoint=http://${OPG_LPA_COMMON_S3_ENDPOINT} \
--region=${DEFAULT_REGION} \
--bucket=${OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET}

# ----------------------------------------------------------
# PERFORMANCE PLATFORM
/usr/local/bin/waitforit -address=tcp://${OPG_LPA_COMMON_LAMBDA_ENDPOINT} -timeout 60 -retry 6000 -debug

# Add queue for performance platform jobs
echo "Adding queue for perfplat"
aws sqs create-queue \
--queue-name=${OPG_LPA_COMMON_PERFPLAT_QUEUE_NAME} \
--attributes="$ATTR" \
--region=${DEFAULT_REGION} \
--endpoint=http://${OPG_LPA_COMMON_SQS_ENDPOINT}

# Recreate the worker lambda
COMMAND="aws lambda list-functions \
--endpoint=http://${OPG_LPA_COMMON_LAMBDA_ENDPOINT} \
--region=${DEFAULT_REGION} | jq '.Functions[] | select(.FunctionName == \"${PERFPLAT_LAMBDA_NAME}\")'"

result=`echo ${COMMAND} | sh`

if [ "${result}" != "" ] ; then
    echo "Lambda exists; removing"

    aws lambda delete-function \
    --function-name=${PERFPLAT_LAMBDA_NAME} \
    --endpoint=http://${OPG_LPA_COMMON_LAMBDA_ENDPOINT} \
    --region=${DEFAULT_REGION}
fi

echo "Zipping perfplat worker code for deployment as lambda"
cd /perfplat-src/
zip /tmp/handler.zip ./handler.py
cd /app/

echo "Creating perfplat worker lambda"
aws lambda create-function \
--endpoint=http://${OPG_LPA_COMMON_LAMBDA_ENDPOINT} \
--region=${DEFAULT_REGION} \
--function-name=${PERFPLAT_LAMBDA_NAME} \
--runtime=python3.7 \
--handler=handler.exec \
--memory-size=128 \
--zip-file=fileb:///tmp/handler.zip \
--role=arn:aws:iam::000000000000:role/irrelevant:role/irrelevant

# Trigger the worker lambda via events on the perfplat queue
echo "Adding trigger to connect perfplat worker to queue"
aws lambda create-event-source-mapping \
--endpoint=http://${OPG_LPA_COMMON_LAMBDA_ENDPOINT} \
--region=${DEFAULT_REGION} \
--function-name=${PERFPLAT_LAMBDA_NAME} \
--event-source-arn=arn:aws:sqs:eu-west-1:000000000000:perfplat-queue.fifo