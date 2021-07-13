import logging

from perfplatcommon.db import Database
from .config import CONFIG


LOGGER = logging.getLogger()
LOGGER.setLevel(logging.DEBUG)

"""
Event looks like this:
{'Records': [{'body': '{"month": 4, "year": 2021}', 'receiptHandle': 'qwdnybcmqvrmqfshkqsceixwsotoketmradbjchyqoyduslllvcxifnguirrrenopobzeafliiqfnlkmvnxucvsdootfwkyayfhgylppdemkwdkzyzapanodqhqsaodczwngbayxlqringkymutgbyrlnfuebianrpjrijphmmmehluijsjghguzd', 'md5OfBody': 'c7845117ec93e956facc5084e61f6249', 'eventSourceARN': 'arn:aws:sqs:eu-west-1:000000000000:perfplat-queue.fifo', 'eventSource': 'aws:sqs', 'awsRegion': 'eu-west-1', 'messageId': '42029418-3b9d-4395-709e-66431088155e', 'attributes': {}, 'messageAttributes': {}, 'md5OfMessageAttributes': None, 'sqs': True}]}
"""
def exec(event, context):
    db = Database(CONFIG['db_conn_str'])
    LOGGER.debug(event)
    return 'MESSAGE RECEIVED - CHECKING CONN STR'