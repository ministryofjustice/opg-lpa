from bs4 import BeautifulSoup
from common.config import logger


def get_csrf_token(client, url):

    logger.debug("Getting csrf token for %s", url)
    response = client.get(url)
    if response.status_code != 200:
        logger.warning(
            "Could not get csrf token for %s. Status code returned ",
            url,
            response.status_code,
        )
        return

    soup = BeautifulSoup(response.text, "html.parser")
    hidden_tags = soup.find_all("input", type="hidden")
    csrf_token = None

    for tag in hidden_tags:
        if tag["name"].startswith("secret_"):
            csrf_token = tag["value"]
            csrf_token_name = tag["name"]
            logger.debug("Found csrf token for %s", url)
            return csrf_token_name, csrf_token

    if csrf_token is None:
        logger.warning("Could not find csrf token for %s", url)
        return
