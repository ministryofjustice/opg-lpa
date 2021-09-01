import json

import requests
import pytest



@pytest.mark.run(order=1)
def test_publish(server):

    with server.app_context():
        test_data = {}

        test_headers = {"Content-Type": "application/json"}

        expected_return = {"Response": "OK"}

        r = requests.post(
            server.url + "/publish", headers=test_headers, data=json.dumps(test_data)
        )
        assert r.status_code == 200
        assert r.json() == expected_return
