#!/bin/sh

REGION=eu-west-1

echo "Waiting for DynamoDB to be available..."
until awslocal dynamodb list-tables --endpoint=$DYNAMODB_URL --region=${REGION} 2>/dev/null; do
  echo "DynamoDB not ready, retrying..."
  sleep 2
done

echo 'creating dynamo tables'

awslocal dynamodb create-table \
--attribute-definitions="AttributeName=id,AttributeType=S" \
--table-name=Sessions \
--key-schema="AttributeName=id,KeyType=HASH" \
--provisioned-throughput="ReadCapacityUnits=100,WriteCapacityUnits=100" \
--region=${REGION} \
--endpoint=$DYNAMODB_URL

awslocal dynamodb create-table \
--attribute-definitions="AttributeName=id,AttributeType=S" \
--table-name=Locks \
--key-schema="AttributeName=id,KeyType=HASH" \
--provisioned-throughput="ReadCapacityUnits=100,WriteCapacityUnits=100" \
--region=${REGION} \
--endpoint=$DYNAMODB_URL

awslocal dynamodb create-table \
--attribute-definitions="AttributeName=id,AttributeType=S" \
--table-name=Properties \
--key-schema="AttributeName=id,KeyType=HASH" \
--provisioned-throughput="ReadCapacityUnits=100,WriteCapacityUnits=100" \
--region=${REGION} \
--endpoint=$DYNAMODB_URL

echo "Waiting for SQS to be available..."
until awslocal sqs list-queues --endpoint=http://localstack:4566 --region=${REGION} 2>/dev/null; do
  echo "SQS not ready, retrying..."
  sleep 2
done

echo 'creating SQS queue'

ATTR="MessageRetentionPeriod=3600,\
FifoQueue=true,\
ContentBasedDeduplication=true,\
VisibilityTimeout=90"

awslocal sqs create-queue \
--queue-name=${OPG_LPA_COMMON_PDF_QUEUE_NAME} \
--attributes="${ATTR}" \
--region=${REGION} \
--endpoint=http://${OPG_LPA_COMMON_SQS_ENDPOINT}

echo "Waiting for S3 to be available..."
until awslocal s3api list-buckets --endpoint=http://localstack:4566 --region=${REGION} 2>/dev/null; do
  echo "S3 not ready, retrying..."
  sleep 2
done

echo 'creating S3 bucket'

awslocal s3api create-bucket \
--endpoint=http://localstack:4566 \
--region=${REGION} \
--bucket=${OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET} \
--create-bucket-configuration LocationConstraint=eu-west-1

echo 'localstack initialisation complete'
