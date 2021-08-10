import sys
import os

sys.path.append(os.path.dirname(__file__))

from flask import request, Response

import connexion

def rewrite_bad_request(response):
    if response.status_code == 400:
        validation_message = {
            "errors": [
                {"code": "OPGDATA-API-INVALIDREQUEST", "message": "Invalid Request"},
            ]
        }

        response = Response(
            json.dumps(validation_message),
            status=400,
            mimetype="application/vnd.opg-data.v1+json",
        )
    return response


mock = connexion.FlaskApp(__name__, specification_dir="../openapi/")
mock.app.after_request(rewrite_bad_request)
mock.add_api("lpa-make-performance-platform-openapi-v1.yml", strict_validation="true")
mock.run(port=4343)