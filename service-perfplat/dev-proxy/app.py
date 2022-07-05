import logging
from os import environ as env

import requests


LOGGER = logging.getLogger()
LOGGER.setLevel(logging.DEBUG)


def handler(event, context):
    target_url = env.get("OPG_LPA_PERFPLAT_WORKER_LAMBDA_URL", None)
    LOGGER.debug(target_url)
    response = requests.post(target_url, json=event)
    LOGGER.debug(response.text)
    return "GOT A RESPONSE"
