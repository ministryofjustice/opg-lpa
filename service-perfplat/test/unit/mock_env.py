import os

import pytest


@pytest.fixture()
def mock_env_setup(monkeypatch):
    monkeypatch.setenv("LOGGER_LEVEL", "DEBUG")
    monkeypatch.setenv("ENVIRONMENT", "mock")
    monkeypatch.setenv("API_VERSION", "v1")
