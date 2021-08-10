import json

import requests
import pytest



@pytest.mark.run(order=1)
def test_check(server):

    with server.app_context():
        test_headers = {"Content-Type": "application/json"}

        expected_return = {"Response": "OK"}

        r = requests.get(
            server.url + "/check", headers=test_headers
        )
        assert r.status_code == 200
        assert r.json() == expected_return
