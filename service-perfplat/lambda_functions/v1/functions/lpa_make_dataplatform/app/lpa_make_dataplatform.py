from .flask_lambda import FlaskLambda
from . import create_app


lambda_handler = create_app(FlaskLambda)

