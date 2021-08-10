
from flask import jsonify
from werkzeug.http import HTTP_STATUS_CODES


def error_message(code, message):
    """
    error_message wraps an error into payload format expected by the API client
    """

    return (
        jsonify(
            {
                "isBase64Encoded": False,
                "statusCode": code,
                "headers": {"Content-Type": "application/json"},
                "body": {
                    "error": {
                        "code": HTTP_STATUS_CODES.get(code, code),
                        "message": message,
                    }
                },
            }
        ),
        code,
    )

