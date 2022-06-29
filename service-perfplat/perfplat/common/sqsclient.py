import json
import sys
from typing import Any, Optional, Union

import boto3
import botocore
import logging


class SqsClient:
    """
    Thin wrapper round the boto3 SQS client tied to a specific queue.
    """

    # Set on all messages to the queue
    MESSAGE_GROUP_ID = "perfplat"

    def __init__(self, queue_name: str, client: boto3.session = None) -> None:
        """
        :param queue_name: str; name of the SQS queue
        :param client: boto3 SQS client; if not set, create one pointing
            at local localstack on default port (4566)
        """
        if client is None:
            client = boto3.client(
                aws_access_key_id="accesskey",
                aws_secret_access_key="secretkey",
                region_name="eu-west-1",
                verify=False,
                service_name="sqs",
                endpoint_url="http://localhost:4566",
            )

        self.client = client

        self.queue_name = queue_name
        self.logger = logging.getLogger()

    def list_queues(self) -> dict:
        """
        List the SQS queues on the endpoint.

        :return: dict; result of calling boto3 SQS client list_queues() method
        """
        return self.client.list_queues()

    def get_queue_url(self) -> Optional[str]:
        """
        Get the fully-qualified URL for the queue.

        :return: None if error occurred while getting URL, otherwise queue's URL
        """
        try:
            return self.client.get_queue_url(QueueName=self.queue_name)["QueueUrl"]
        except botocore.exceptions.ClientError as e:
            self.logger.error(e)
            return None

    def queue_exists(self) -> bool:
        """
        Check whether the queue exists.

        :return True if the queue's URL could be fetched successfully, otherwise False
        """
        return self.get_queue_url() is not None

    def create_queue(self) -> bool:
        """
        Create the queue.

        :return: False if error occurred while creating the queue, True otherwise
        """
        try:
            self.client.create_queue(QueueName=self.queue_name)
            return True
        except botocore.exceptions.ClientError as e:
            self.logger.error(e)
            return False

    def send_message(self, json_payload: Any) -> Union[bool, dict]:
        """
        Send a message to the queue.

        Note that MESSAGE_GROUP_ID is used as the message group for all
        messages sent to the queue.

        :param json_payload: any; data to serialise to JSON and use
            as MessageBody
        :return: response from queue if it exists, False otherwise
        """
        url = self.get_queue_url()

        if url is None:
            return False

        return self.client.send_message(
            QueueUrl=url,
            MessageBody=json.dumps(json_payload),
            MessageGroupId=self.MESSAGE_GROUP_ID,
        )
