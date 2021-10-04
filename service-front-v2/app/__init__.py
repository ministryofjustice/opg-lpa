from flask import Flask
from flask_compress import Compress
from flask_talisman import Talisman
from jinja2 import ChoiceLoader, PackageLoader, PrefixLoader
from aws_xray_sdk.core import xray_recorder
from aws_xray_sdk.ext.flask.middleware import XRayMiddleware

app = Flask(__name__, static_url_path="/assets")
xray_recorder.configure(service='Make An LPA')
XRayMiddleware(app, xray_recorder)

app.jinja_loader = ChoiceLoader(
    [
        PackageLoader("app"),
        PrefixLoader({"govuk_frontend_jinja": PackageLoader("govuk_frontend_jinja")}),
    ]
)

app.jinja_env.trim_blocks = True
app.jinja_env.lstrip_blocks = True

csp = {
    "default-src": "'self'",
    "script-src": [
        "'self'",
        "'sha256-+6WnXIl4mbFTCARd8N3COQmT3bJJmo32N8q8ZSQAIcU='",
        "'sha256-l1eTVSK8DTnK8+yloud7wZUqFrI0atVo6VlC6PJvYaQ='",
    ],
    "img-src": ["data:", "'self'"],
}

Compress(app)
Talisman(app, content_security_policy=csp, force_https=False)

from app import routes
