from os import path

from setuptools import setup

setup(
    name='perfplat',
    version='0.1.0',
    description='Performance platform background worker and API',
    long_description='',
    long_description_content_type='text/plain',
    packages=[
        'perfplat',
    ],
    python_requires='>=3.7, <4',
    install_requires=[
        'SQLAlchemy',
        'psycopg2',
        'Flask',
        'flask-lambda',
        'itsdangerous',
        'Werkzeug',
        'connexion',
        'swagger-ui-bundle'
    ],
    extras_require={
        'dev': [
            'boto3',
            'localstack-client',
            'alembic',
            'nose',
            'openapi-spec-validator',
            'requests',
            'moto',
            'coverage',
            'pytest',
            'pytest-cases',
            'pytest-cov',
            'pytest-ordering',
            'pytest-env'
        ],
    },
    scripts=[
        './bin/queuecli'
    ],
)
