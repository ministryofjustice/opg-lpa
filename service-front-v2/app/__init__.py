from flask import Flask
from flask_compress import Compress
from flask_talisman import Talisman
from flask_wtf.csrf import CSRFProtect
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
    "default-src": "'self'"
}

Compress(app)
Talisman(app, content_security_policy=csp, strict_transport_security_max_age=3600)
csrf = CSRFProtect(app)

from app import routes
