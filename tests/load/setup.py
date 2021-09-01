"""
See:
https://packaging.python.org/guides/distributing-packages-using-setuptools/
https://github.com/pypa/sampleproject
"""

# Always prefer setuptools over distutils
import json

from setuptools import find_packages, setup
from os import path

setup(
    name='make_an_lpa_load_tests',
    version='0.0.1',
    description='Load tests for Make an LPA',
    packages=[
        'tests',
    ],
    python_requires='>=3.6, <4',
    install_requires=[
        'beautifulsoup4',
        'locust',
        'requests',
    ],
    scripts=[
        './bin/run_load_tests.sh',
    ]
)
