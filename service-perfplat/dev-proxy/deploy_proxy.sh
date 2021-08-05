#!/bin/sh
# Script intended to run in Dockerfile-worker-proxy container
# to build and deploy proxy lambda for local dev
OPG_LPA_LOCALSTACK_ENDPOINT=localstack:4566
DEFAULT_REGION=eu-west-1
LAMBDA_NAME=perfplatworkerproxy

# In dev, this queue is set up by local-config
PERFPLAT_QUEUE_ARN=arn:aws:sqs:eu-west-1:000000000000:perfplat-queue.fifo

# Determine the IP address of the perfplatworker
#PERFPLAT_WORKER_IP=`getent hosts perfplatworker | awk '{print $1}'`

# The URL of the "real" lambda we actually want to wire up to the SQS queue
# but are instead proxying through this lambda
OPG_LPA_PERFPLAT_WORKER_LAMBDA_URL=http://perfplatworker:8080/2015-03-31/functions/function/invocations

# Wait for localstack to come up
/usr/local/bin/waitforit -address=tcp://${OPG_LPA_LOCALSTACK_ENDPOINT} -timeout 60 -retry 6000 -debug

# Recreate the worker lambda proxy;
# we do this by removing then re-adding the lambda, as this also
# prevents duplication of the event source mapping
COMMAND="aws lambda list-functions \
--endpoint=http://${OPG_LPA_LOCALSTACK_ENDPOINT} \
--region=${DEFAULT_REGION} | jq '.Functions[] | select(.FunctionName == \"${LAMBDA_NAME}\")'"

result=`echo ${COMMAND} | sh`

if [ "${result}" != "" ] ; then
    echo "Lambda exists; removing"

    aws lambda delete-function \
    --function-name=${LAMBDA_NAME} \
    --endpoint=http://${OPG_LPA_LOCALSTACK_ENDPOINT} \
    --region=${DEFAULT_REGION}
fi

echo "Installing lambda proxy dependencies"
mkdir -p /service-perfplat/dev-proxy/build
pip3 install --upgrade --target /service-perfplat/dev-proxy/build requests

echo "Zipping lambda proxy ready for deployment"
cd /service-perfplat/dev-proxy/build/
zip -r /tmp/perfplatworkerproxy.zip .
cd /service-perfplat/dev-proxy/
zip -g /tmp/perfplatworkerproxy.zip ./app.py

echo "Creating perfplat worker proxy lambda"
aws lambda create-function \
--endpoint=http://${OPG_LPA_LOCALSTACK_ENDPOINT} \
--region=${DEFAULT_REGION} \
--function-name=${LAMBDA_NAME} \
--runtime=python3.8 \
--handler=app.handler \
--memory-size=128 \
--zip-file=fileb:///tmp/perfplatworkerproxy.zip \
--role=arn:aws:iam::000000000000:role/irrelevant:role/irrelevant \
--environment \
"Variables={OPG_LPA_PERFPLAT_WORKER_LAMBDA_URL=${OPG_LPA_PERFPLAT_WORKER_LAMBDA_URL}}"

# Trigger the worker lambda via events on the perfplat queue
echo "Adding trigger to connect perfplat worker to queue"
aws lambda create-event-source-mapping \
--endpoint=http://${OPG_LPA_LOCALSTACK_ENDPOINT} \
--region=${DEFAULT_REGION} \
--function-name=${LAMBDA_NAME} \
--event-source-arn=${PERFPLAT_QUEUE_ARN}