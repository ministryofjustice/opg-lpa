import json
import os

from flask import Blueprint
from flask import request, jsonify

from .errors import error_message
from .endpoints import handle_dummy

version = os.getenv("API_VERSION")
api = Blueprint("api", __name__, url_prefix=f"/{version}"i)


@api.route("/healthcheck", methods=["GET"])
def healthcheck_route():
    result, status_code = handle_dummy()

    return jsonify(result), status_code

@api.route("/publish", methods=["POST"])
def publish_route():
    result, status_code = handle_dummy()

    return jsonify(result), status_code

@api.route("/retreive", methods=["GET"])
def retreive_route():
    result, status_code = handle_dummy()

    return jsonify(result), status_code

@api.route("/check", methods=["GET"])
def check_route():
    result, status_code = handle_dummy()

    return jsonify(result), status_code

@api.route("/remove", methods=["DELETE"])
def healthcheck_route():
    result, status_code = handle_dummy()

    return jsonify(result), status_code

@api.app_errorhandler(404)
def handle404(error=None):
    return error_message(404, "Not found url {}".format(request.url))


@api.app_errorhandler(405)
def handle405(error=None):
    return error_message(405, "Method not supported")


@api.app_errorhandler(400)
def handle400(error=None):
    return error_message(400, "Bad payload")


@api.app_errorhandler(500)
def handle500(error=None):
    return error_message(500, f"Something went wrong: {error}")
