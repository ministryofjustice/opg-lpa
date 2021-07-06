import logging

LOGGER = logging.getLogger()
LOGGER.setLevel(logging.DEBUG)

def exec(event, context):
    LOGGER.debug('I WAS INVOKED')
    return {"hello": "world"}