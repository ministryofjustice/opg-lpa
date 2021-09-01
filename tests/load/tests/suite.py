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

from lpaapi import createAndActivateUser, deleteLpa, deleteUser, makeNewLpa, updateUserDetails

# globally prevent insecure request warnings caused by our self-signed cert
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


CONFIG = load_config()

# turn on verbose HTTP request logging; NB this is *very* verbose
# but occasionally useful
if CONFIG['requests_debugging']:
    import http.client as http_client
    http_client.HTTPConnection.debuglevel = 1


class VisitDashboardBehaviour(SequentialTaskSet):
    @task
    def go_to_login_page(self):
        response = self.client.get('/login', name='1. go to login page')

    @task
    def submit_credentials(self):
        data = {
            'email': self.user.username,
            'password': self.user.password,
        }
        self.client.post('/login', data=data, name='2. submit credentials')

    @task
    def go_to_dashboard(self):
        with self.client.get('/user/dashboard', catch_response=True, name='3. go to dashboard') as response:
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
        self.client.get('/logout', name='4. logout')


class MakeAnLpaUser(HttpUser):
    tasks = [
        VisitDashboardBehaviour,
    ]

    # wait time between requests by this user
    wait_time_secs = 1

    password = 'Pass1234'

    # populated when user is created and activated
    user_id = None

    # populated when LPA is added for user
    lpa_id = None

    def __init__(self, *args, **kwargs):
        self.host = CONFIG['host']

        # create a random username
        self.username = f'{uuid1()}@uat.justice.gov.uk'

        super().__init__(*args, **kwargs)

    def on_start(self):
        # turn off client cert verification
        self.client.verify = False

        # create the user on the back-end system
        logging.info(f'creating and activating user: {self.username} / {self.password}')
        response = createAndActivateUser(self.username, self.password)
        if not response['success']:
            raise Exception('unable to create user')
        self.user_id = response['user_id']

        # set basic user details (otherwise user will be prompted
        # for this after logging in and won't be able to see their LPAs)
        logging.info(f'populating "about you" for user {self.username}')
        user = User(self.user_id, self.username)
        updateUserDetails(self.username, self.password, user.build_details())

        # add a fake LPA for this user; we store its ID so we can
        # check for related text in dashboard HTML (and remove it on stop)
        logging.info(f'making LPA for user {self.username}')
        self.lpa_id = makeNewLpa(self.username, self.password)

    def on_stop(self):
        # clean up generated user and LPA (if they exist)
        if self.lpa_id is not None:
            logging.info(f'cleaning up LPA with ID {self.lpa_id} for user {self.username}')
            deleteLpa(self.lpa_id, self.username, self.password)

        if self.user_id is not None:
            status = deleteUser(self.user_id, self.username, self.password).status_code
            logging.info(f'cleaned up user {self.username} with ID {self.user_id}; status: {status}')

    def wait_time(self):
        return self.wait_time_secs
