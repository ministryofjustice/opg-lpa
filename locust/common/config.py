import logging
import os

LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO").upper()
logger = logging.getLogger(__name__)
logger.setLevel(LOG_LEVEL)

DISABLE_SSL_VERIFY = os.getenv("DISABLE_SSL_VERIFY", False)
