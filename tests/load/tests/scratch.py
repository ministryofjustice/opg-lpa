import os
import sys

python_api_client_path = os.path.join(os.path.dirname(__file__), '..', 'python-api-client')
sys.path.append(python_api_client_path)

from lpaapi import createAndActivateUser

print(createAndActivateUser('elliot2@townx.org', 'Pass1234'))
