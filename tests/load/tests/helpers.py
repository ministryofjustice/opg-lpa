import json
import os
import uuid

from datetime import datetime

DEFAULT_LOAD_TEST_CONFIG_FILE_PATH = os.path.join(
    os.path.dirname(__file__), "..", "load-test-config.json"
)


def get_env() -> str:
    env = os.environ.get("LOAD_TEST_ENV", "local")
    if env == "":
        env = "local"
    return env


def load_config(config_file_path=None, env=get_env()) -> dict:
    """
    Returns a configuration dict loaded from a JSON file for the specified env

    If argument is not passed, will use the value from
    LOAD_TEST_CONFIG_FILE_PATH env var.

    If that isn't set, defaults to an expected file path.

    :param config_file_path: str; path to config file to load
    :param env: str; environment to load config for

    :return: dict
    """
    if config_file_path is None:
        config_file_path = os.environ.get(
            "LOAD_TEST_CONFIG_FILE_PATH", DEFAULT_LOAD_TEST_CONFIG_FILE_PATH
        )
    if config_file_path == "":
        config_file_path = DEFAULT_LOAD_TEST_CONFIG_FILE_PATH

    with open(config_file_path, "r") as config_file:
        return json.loads(config_file.read())[env]
