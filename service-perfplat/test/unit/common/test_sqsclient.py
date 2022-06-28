from unittest.mock import MagicMock

import boto3
import botocore

from perfplat.common.sqsclient import SqsClient


session_mock = MagicMock(boto3.session)
client = SqsClient("foo", client=session_mock)


def test_list_queues():
    session_mock.list_queues = MagicMock()
    client.list_queues()
    session_mock.list_queues.assert_called_once()


def test_get_queue_url():
    expected = "http://foo/"
    session_mock.get_queue_url = MagicMock(return_value={"QueueUrl": expected})
    assert client.get_queue_url() == expected
    session_mock.get_queue_url.assert_called_once_with(QueueName="foo")


def test_get_queue_url_exception():
    session_mock.get_queue_url = MagicMock(
        side_effect=botocore.exceptions.ClientError({}, "")
    )
    assert client.get_queue_url() == None
    session_mock.get_queue_url.assert_called_once_with(QueueName="foo")


def test_queue_exists():
    session_mock.get_queue_url = MagicMock(return_value={"QueueUrl": "http://foo/"})
    assert client.queue_exists() == True
    session_mock.get_queue_url.assert_called_once_with(QueueName="foo")


def test_queue_exists_exception():
    session_mock.get_queue_url = MagicMock(
        side_effect=botocore.exceptions.ClientError({}, "")
    )
    assert client.queue_exists() == False
    session_mock.get_queue_url.assert_called_once_with(QueueName="foo")


def test_create_queue():
    session_mock.create_queue = MagicMock()
    assert client.create_queue() == True
    session_mock.create_queue.assert_called_once_with(QueueName="foo")


def test_create_queue_exception():
    session_mock.create_queue = MagicMock(
        side_effect=botocore.exceptions.ClientError({}, "")
    )
    assert client.create_queue() == False
    session_mock.create_queue.assert_called_once_with(QueueName="foo")


def test_send_message():
    url = "http://foo/"
    session_mock.get_queue_url = MagicMock(return_value={"QueueUrl": url})

    response = {"HTTPStatusCode": 200}
    session_mock.send_message = MagicMock(return_value=response)

    assert client.send_message({}) == response
    session_mock.send_message.assert_called_once_with(
        QueueUrl=url,
        MessageBody="{}",
        MessageGroupId="perfplat",
    )


def test_send_message_no_queue_url():
    expected = None
    session_mock.get_queue_url = MagicMock(
        side_effect=botocore.exceptions.ClientError({}, "")
    )
    assert client.send_message({}) == False
