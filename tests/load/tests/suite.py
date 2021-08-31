import urllib3

from locust import HttpUser, SequentialTaskSet, task

from tests.helpers import load_config

# globally prevent insecure request warnings caused by our self-signed cert
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


class DashboardBehaviour(SequentialTaskSet):
    @task
    def login(self):
        self.client.get('/login')


class TaskUser(HttpUser):
    tasks = [
        DashboardBehaviour,
    ]

    # wait time between requests by this user
    wait_time_secs = 0.5

    def __init__(self, *args, **kwargs):
        self.config = load_config()
        self.host = self.config['host']
        super().__init__(*args, **kwargs)

    def on_start(self):
        # turn off client cert verification
        self.client.verify = False

    def wait_time(self):
        return self.wait_time_secs
