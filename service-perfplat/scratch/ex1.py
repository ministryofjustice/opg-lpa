import sys

import botocore
import localstack_client.session as boto3

client = boto3.client('sqs')

def list_queues():
    return client.list_queues()

def queue_exists(queue_name):
    try:
        client.get_queue_url(QueueName=queue_name)
        return True
    except botocore.exceptions.ClientError as e:
        return False

def create_queue(queue_name):
    if not queue_exists(queue_name):
        try:
            client.create_queue(QueueName=queue_name)
            return True
        except botocore.exceptions.ClientError as e:
            return False
    return False

def send_message(queue_name):
    url = client.get_queue_url(QueueName=queue_name)['QueueUrl']
    return client.send_message(
        QueueUrl=url,
        MessageBody='I am a message',
        MessageGroupId='one'
    )

if __name__ == '__main__':
    if len(sys.argv) < 2:
        sys.stderr.write('Usage: ' + sys.argv[0] + ' <action> <params...>\n')
        sys.exit(1)

    action = sys.argv[1]
    if action == 'list':
        print(list_queues())
    elif action == 'check':
        queue_name = sys.argv[2]
        print(queue_exists(queue_name))
    elif action == 'create':
        queue_name = sys.argv[2]
        print(create_queue(queue_name))
    elif action == 'send':
        queue_name = sys.argv[2]
        print(send_message(queue_name))