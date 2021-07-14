from os import path

from setuptools import setup

setup(
    name='perfplat',
    version='0.1.0',
    description='Performance platform background worker and API',
    long_description='',
    long_description_content_type='text/plain',
    packages=[
        'perfplatcli',
        'perfplatworker',
        'perfplatcommon',
    ],
    python_requires='>=3.7, <4',
    install_requires=[
        'requests',
        'SQLAlchemy',
        'psycopg2',
    ],
    extras_require={
        'dev': [
            'boto3',
            'localstack-client',
            'alembic',
        ],
    }
)
