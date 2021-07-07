from os import path

from setuptools import setup

setup(
    name='perfplatworker',
    version='0.1.0',
    description='Performance platform background worker',
    long_description='',
    long_description_content_type='text/plain',
    packages=[
        'perfplatworker',
    ],
    python_requires='>=3.7, <4',
    install_requires=[
        'requests',
    ],
    extras_require={
        'dev': [
        ],
    }
)
