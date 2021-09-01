import logging
import os
import sys
import urllib3
from uuid import uuid1

from locust import HttpUser, SequentialTaskSet, task

from tests.helpers import load_config

# put the lpaapi.py functions on the PYTHONPATH
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..', 'python-api-client'))

from lpaapi import createAndActivateUser, makeNewLpa

# globally prevent insecure request warnings caused by our self-signed cert
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

import http.client as http_client
http_client.HTTPConnection.debuglevel = 1

"""
logging.basicConfig()
logging.getLogger().setLevel(logging.DEBUG)
requests_log = logging.getLogger("requests.packages.urllib3")
requests_log.setLevel(logging.DEBUG)
requests_log.propagate = True
"""


class VisitDashboardBehaviour(SequentialTaskSet):
    @task
    def go_to_login_page(self):
        response = self.client.get('/login')
        print(f'{self.user.username}, {self.user.password}')

    @task
    def submit_credentials(self):
        data = {
            'email': self.user.username,
            'password': self.user.password,
        }
        self.client.post('/login', data=data)

    @task
    def go_to_dashboard(self):
        response = self.client.get('/user/dashboard')

        print(response.text)

        # check the user's LPA is listed

    @task
    def logout(self):
        self.client.get('/logout')


class TasksUser(HttpUser):
    tasks = [
        VisitDashboardBehaviour,
    ]

    # wait time between requests by this user
    wait_time_secs = 1

    def __init__(self, *args, **kwargs):
        self.config = load_config()
        self.host = self.config['host']
        super().__init__(*args, **kwargs)

    def on_start(self):
        # turn off client cert verification
        self.client.verify = False

        # create a random username
        self.username = f'{uuid1()}@uat.justice.gov.uk'

        self.password = 'Pass1234'

        # create the user on the back-end system
        response = createAndActivateUser(self.username, self.password)
        if not response['success']:
            raise Exception('Unable to create user')
        self.user_id = response['user_id']

        # TODO set basic user data (otherwise user will be prompted
        # for this after logging in)

        # add a fake LPA for this user
        self.lpa_id = makeNewLpa(self.username, self.password)

    # TODO clean up generated user

    def wait_time(self):
        return self.wait_time_secs
