def create_app(Flask):
    app = Flask(__name__)

    from .api import api as api_blueprint

    app.register_blueprint(api_blueprint)

    return app
