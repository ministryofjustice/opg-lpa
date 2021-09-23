from config import Config
from flask import Flask
from flask_assets import Bundle, Environment
from flask_compress import Compress
from flask_talisman import Talisman
from flask_wtf.csrf import CSRFProtect
from govuk_frontend_wtf.main import WTFormsHelpers
from jinja2 import ChoiceLoader, PackageLoader, PrefixLoader

app = Flask(__name__, static_url_path="/assets")
app.config.from_object(Config)

app.jinja_loader = ChoiceLoader(
    [
        PackageLoader("app"),
        PrefixLoader(
            {
                "govuk_frontend_jinja": PackageLoader("govuk_frontend_jinja"),
                "govuk_frontend_wtf": PackageLoader("govuk_frontend_wtf"),
            }
        ),
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
csrf = CSRFProtect(app)
WTFormsHelpers(app)

assets = Environment(app)
js = Bundle("src/js/*.js", filters="jsmin", output="dist/js/custom-%(version)s.js")
assets.register("js", js)

from app import routes
