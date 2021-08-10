import json

import requests
import pytest



@pytest.mark.run(order=1)
def test_remove(server):

    with server.app_context():
        test_data = {}

        test_headers = {"Content-Type": "application/json"}

        expected_return = {"Response": "OK"}

        r = requests.delete(
            server.url + "/remove", headers=test_headers, data=json.dumps(test_data)
        )
        assert r.status_code == 200
        assert r.json() == expected_return
