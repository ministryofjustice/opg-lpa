"""
import os
import sys
from uuid import uuid1

python_api_client_path = os.path.join(os.path.dirname(__file__), '..', '..', 'python-api-client')
sys.path.append(python_api_client_path)

from lpaapi import activateUser, createUser

password = 'Pass1234'
username = f'{uuid1()}@uat.justice.gov.uk'

print(f'creating user/pass: {username}, {password}')

response = createUser(username, password)
activation_token = response['activation_token']
print(response)

print(f'activating user')

print(activateUser(activation_token))
"""

import http.client as http_client
http_client.HTTPConnection.debuglevel = 1

from requests import Request, Session

session = Session()
session.verify = False

response = session.get('https://localhost:7002/home')
print(session.cookies.keys())

req = Request('GET', 'https://localhost:7002/home')
req = session.prepare_request(req)
print(req.headers)

response = session.send(req)
