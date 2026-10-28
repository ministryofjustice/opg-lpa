from bs4 import BeautifulSoup
from common.config import logger
import re


def get_csrf_token(client, url):
    logger.debug("Getting csrf token for %s", url)
    # Use regex to replace the 11 digit ID in the url (e.g. 16447851592) with [id] so that we can group the urls in the stats

    grouped_url = re.sub(r"\d{9,12}", "[id]", url)

    response = client.get(url, name=grouped_url)
    if response.status_code != 200:
        logger.warning(
            "Could not get csrf token for %s. Status code returned %s",
            url,
            response.status_code,
        )
        return

    soup = BeautifulSoup(response.text, "html.parser")
    hidden_tags = soup.find_all("input", type="hidden")

    # LPA forms carry the LPA version (optimistic locking) which must be posted back
    version_tag = soup.find("input", attrs={"type": "hidden", "name": "version"})
    client.form_version = version_tag.get("value", "") if version_tag else ""
    csrf_token = None

    for tag in hidden_tags:
        if tag.get("name") == "__csrf":
            csrf_token = tag["value"]
            csrf_token_name = tag["name"]
            logger.debug("Found csrf token for %s", url)
            return csrf_token_name, csrf_token

    if csrf_token is None:
        logger.warning("Could not find csrf token for %s", url)
        return


def get_form_version(client):
    """Version of the LPA form last fetched via get_csrf_token."""
    return getattr(client, "form_version", "")
