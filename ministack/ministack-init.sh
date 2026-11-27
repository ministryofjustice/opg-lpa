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
until awslocal sqs list-queues --endpoint=http://ministack:4566 --region=${REGION} 2>/dev/null; do
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
until awslocal s3api list-buckets --endpoint=http://ministack:4566 --region=${REGION} 2>/dev/null; do
  echo "S3 not ready, retrying..."
  sleep 2
done

echo 'creating S3 bucket'

awslocal s3api create-bucket \
--endpoint=http://ministack:4566 \
--region=${REGION} \
--bucket=${OPG_LPA_COMMON_PDF_CACHE_S3_BUCKET} \
--create-bucket-configuration LocationConstraint=eu-west-1

echo 'creating One Login signing keys secret'

# Throwaway RSA (RS256) key for local dev only, base64-encoded so the file has no PEM marker.
awslocal secretsmanager create-secret \
--endpoint=http://ministack:4566 \
--region=${REGION} \
--name=local/onelogin-signing-keys \
--secret-string='{"current":"LS0tLS1CRUdJTiBQUklWQVRFIEtFWS0tLS0tCk1JSUV2UUlCQURBTkJna3Foa2lHOXcwQkFRRUZBQVNDQktjd2dnU2pBZ0VBQW9JQkFRQ3RrMUd4TndyQWxoSUIKZjI5OGQ1VTFrdUVZQVFIS3QrbXcrZXE1QzJpMEkyNDBZdWxZVnNJUTl3VjdyaEQ3Y001V0FnNGRSQU8zTmxKSgpad0NlRkVLNDFvMmVsZjFESE5zRFRlT2s5b0dlUlVJcVV2UlpndndCNUhCRFZWU1VMYWdHaWgvTk9meks3Z3pvCjBzeitMWGVQbVFha1Q1b2NZOWxUQ044cU9LbzhINTVuNjdsZEd4OWJTdFEvVjFvYzJ5d0F5TThWajJKeWVlS0sKOVdLWEpBeDRZZ0VFSXlKTDk2OVRYREJWWTVaZHRubWNPQzlnd3BYaGtOTlYyMWZualJTRDViVHllb3p4ZjQ0NQp3Zzdjamc0OFVVNUpNakJ3amZqVmhieUZORmpjOG5pdk1qcHpianZZNS9UZzVicGxRaEdBNzNLR0d5c1JUd1BVCi9vUGs0cWQ5QWdNQkFBRUNnZ0VCQUkrRktWNGJ3Zk05NHdPRUNIM2xMdVhPWGZxSUVxQTQ4NnpXNklNNjhNZEsKS0h4K2NLMFBoNFdaaXRvYU1vYUZYc2IxQ2hDZXhXZS9MY2p0dHkvK1ZyRVpEcDViTEw5S1k3bFd0TzBZdWxKUQp1QVhnODVQejMwWDlTZW9TNjAxZWR2bDVTaFJhWnpDd0p0SjRRdkhFVElEYXFnaEFuWS90NjZ0aHdZemFyNy9uCmsvVXg5dXlNOU03VTVsYnJKaWFUc2RtRk5BMlF1RkVYZmQ1aTJwam54dkx1Qm5zejRCblVvckc3cnhWS2Q2ak8Kd29CanlQSy9KZmtua3hGTkJTeS9rRGlRQittVUVIelpnUjRxd2pBYmwvL0tOZDM3ODJnT1I0TUlSZ3BUV0hLSwpTMkNFdEFlTHl3YlhVSTBQNm5WWnZ4Smd6akgrZkhHaDBHNGxXcS9aQ0MwQ2dZRUEzZ0RpQjAzVzlaRWpsSXp6Ci9FVVd6SVFPeHRHU3NIbDZSNjRyYmVuY0o5NlZIN3ZLTU84ZWZRY0xudnFsZHVienVSOUlKeDdOMGV1UDNTZzMKVVJzdlhxL0NpbHZ5M3dzQ0tLSmdTZVVwUU9zM0VCYzN6bXo5YmNKT2s2UnJOTlpxS1JJV3MxVC9tTmNJcnZJUwpqSlRqd1I4d3VodXdNMTc4OXBDWGN4YTluS3NDZ1lFQXlDZnVxdlRyZ0p0K1pBV296K1FsTGUzbEVTMHdhOHpZCjBJMjdDV3dJZGpUNHdhbVVVTEdlN2l4ZUsyNEoxN3dvU0hnSVZQeFZ4TmZMYXB0ZnliSk8vcGhTc0l0eWRtdHcKUCtFcVJ2YUdpWDFVWEp2V3crWkIwMnVLVFBha2pZVzFsVnplRGNreHRMbU5hajNLNCtjSXdIeVVQZ2s4Mmo3TQpYSGt5VmJpVWZIY0NnWUFUWE5oWjhyd1E1dnZIZFJQQ3FQUDRHbngvY3Ivc2pHdlIzS3NGQnlTZ2haVlVFempyClU2L0JEK0EvbVhUZERmK3I0Y0tWUnVhRXJKcjdtd3BZRkpycEUwTlFkZnlVMzR6c3Bsem5sUWtvRkZ2VkYzRlMKbThidXo4ZXlzT0I0MTBxakVtVFFkMVZqcXoyaXBKSmhtSXJVeVRlaXc4OTJhNDU4b3RmU3ZlVUR5UUtCZ0J4dwprQmo2Yy92eUhDTmFLb0pmaUdqZVJJNE1QYlVUaGpzYjZGZU9ZaVZwb1c3akNZL3RyUWRxaG5ibFM4QzdtMzJ3CmtINkMzQ3o3L0l0SlNITUFRdmtwNjVYcU9ubFpDcm9JNUZqdzdtemF3Z0htb3IxekdpZFFRSFp2RlRLR3JTWnIKNjZGSjFTWTR0ejlCU0hMWmdRVFlycmVoMkdPcVNKOWJHU3ZVN2tSQkFvR0FJOTdVT2k1VUdpV3E0L09xMG5hdApZdHlLZFFhWExOT3QzZzY1MXd1VEVwcnUvRlp0ZUwrL21OeEV4ckhtMzNXbTFIeFFpZFd4Q2xQM1RIZlJzT2U5CkJKVkdWa1pnS3VmK1daYzU0bG9hU29BbEVCeTEyd2FuOUQ1WTFVUCt5OFRTQVB5NW5iMVRLN1AvRGYyVTJPRkMKZklWL3lTL2JTNFRSZU9GbUtOM3FhNzQ9Ci0tLS0tRU5EIFBSSVZBVEUgS0VZLS0tLS0K"}' # pragma: allowlist secret

echo 'ministack initialisation complete'
