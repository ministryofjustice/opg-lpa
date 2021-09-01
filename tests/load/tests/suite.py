import logging
import os
import sys
import urllib3
from uuid import uuid1

from bs4 import BeautifulSoup
from locust import HttpUser, SequentialTaskSet, task

from tests.helpers import load_config
from tests.user import User

# put the lpaapi.py functions on the PYTHONPATH
sys.path.append(os.path.join(os.path.dirname(__file__), '..', '..', 'python-api-client'))

from lpaapi import createAndActivateUser, makeNewLpa, updateUserDetails

# globally prevent insecure request warnings caused by our self-signed cert
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

"""
# TODO trigger from config
# this block turns on verbose HTTP request logging
import http.client as http_client
http_client.HTTPConnection.debuglevel = 1
"""


class VisitDashboardBehaviour(SequentialTaskSet):
    @task
    def go_to_login_page(self):
        response = self.client.get('/login')

    @task
    def submit_credentials(self):
        data = {
            'email': self.user.username,
            'password': self.user.password,
        }
        self.client.post('/login', data=data)

    @task
    def go_to_dashboard(self):
        with self.client.get('/user/dashboard', catch_response=True) as response:
            # check the user's LPA is listed; expected text
            # is in a visually-hidden element
            expected_text = f'LPA A{self.user.lpa_id}'

            if not expected_text in response.text:
                response.failure(f'expected LPA text {expected_text} was not found in response body')

            # load the DOM and check we only have one LPA listed
            soup = BeautifulSoup(response.text, features='html.parser')
            lpa_list_items = soup.select('ul.track-my-lpa > li')
            count_lpas = len(lpa_list_items)
            if count_lpas > 1:
                response.failure(f'expected one LPA in dashboard but found {count_lpas}')

    @task
    def logout(self):
        self.client.get('/logout')


class TasksUser(HttpUser):
    tasks = [
        VisitDashboardBehaviour,
    ]

    # wait time between requests by this user
    wait_time_secs = 1

    password = 'Pass1234'

    def __init__(self, *args, **kwargs):
        self.config = load_config()
        self.host = self.config['host']

        # create a random username
        self.username = f'{uuid1()}@uat.justice.gov.uk'

        super().__init__(*args, **kwargs)

    def on_start(self):
        # turn off client cert verification
        self.client.verify = False

        # create the user on the back-end system
        response = createAndActivateUser(self.username, self.password)
        if not response['success']:
            raise Exception('Unable to create user')
        user_id = response['user_id']

        # set basic user details (otherwise user will be prompted
        # for this after logging in and won't be able to see their LPAs)
        user = User(user_id, self.username)
        updateUserDetails(self.username, self.password, user.build_details())

        # add a fake LPA for this user; we store its ID so we can
        # check for related text in dashboard HTML (and remove it on stop)
        self.lpa_id = makeNewLpa(self.username, self.password)

    def on_stop(self):
        # TODO clean up generated user and LPA
        pass

    def wait_time(self):
        return self.wait_time_secs
