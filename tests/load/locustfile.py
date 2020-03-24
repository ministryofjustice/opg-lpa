import boto3
from faker.providers import person
from faker.providers import address
from realbrowserlocusts import FirefoxLocust
import time
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC


from locust import TaskSet, task
from random import randint

import os
import re

from faker import Faker
fake = Faker('en_GB')
fake.add_provider(person)
fake.add_provider(address)


class LocustUserBehavior(TaskSet):

    def get_s3_client(self):
        self.aws_account_id = "050256574573"
        self.set_iam_role_session()
        self.bucket_name = 'opg-lpa-casper-mailbox'
        self.aws_s3_client = boto3.client(
            's3',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

        self.aws_s3_resource = boto3.resource(
            's3',
            region_name='eu-west-1',
            aws_access_key_id=self.aws_iam_session['Credentials']['AccessKeyId'],
            aws_secret_access_key=self.aws_iam_session['Credentials']['SecretAccessKey'],
            aws_session_token=self.aws_iam_session['Credentials']['SessionToken'])

    def set_iam_role_session(self):
        if os.getenv('CI'):
            role_arn = 'arn:aws:iam::{}:role/ci'.format(
                self.aws_account_id)
        else:
            role_arn = 'arn:aws:iam::{}:role/operator'.format(
                self.aws_account_id)

        sts = boto3.client(
            'sts',
            region_name='eu-west-1',
        )
        session = sts.assume_role(
            RoleArn=role_arn,
            RoleSessionName='get_s3_objects',
            DurationSeconds=900
        )
        self.aws_iam_session = session

    def create_user_credentials(self):
        self.new_user_credentials = {
            'username': 'caspertests+load_test_{}@lpa.opg.service.justice.gov.uk'.format(randint(1, 100000000)),
            'password': 'Pass1234!'
        }

    def refuse_protected(self):
        protected_environments = [
            "https://www.lastingpowerofattorney.service.gov.uk/",
            "https://front.production.lpa.opg.service.justice.gov.uk",
        ]
        if self.locust.host in protected_environments:
            print("do not run load tests against protected environments")
            exit(1)

    def define_actors(self):
        self.account_holder = self.make_a_person("account_holder")
        self.donor = self.make_a_person("donor")
        self.attorney_1 = self.make_a_person("attorney")
        self.attorney_2 = self.make_a_person("attorney")
        self.replacement_attorney_1 = self.make_a_person(
            "replacement_attorney")
        self.replacement_attorney_2 = self.make_a_person(
            "replacement_attorney")
        self.certificate_provider_1 = self.make_a_person(
            "certificate_provider")
        self.certificate_provider_2 = self.make_a_person(
            "certificate_provider")
        self.named_person_1 = self.make_a_person("named_person")
        self.named_person_2 = self.make_a_person("named_person")

    def make_a_person(self, person_type):
        person = {
            'type': person_type,
            'last_name': fake.last_name(),
            'first_name': fake.first_name(),
            'title': fake.prefix(),
            'phone_number': "07700900111",
            'address_1': fake.street_address(),
            'address_2': fake.secondary_address(),
            'address_3': fake.city_prefix(),
            'postcode': fake.postcode(),
            'email': "simulate-delivered-2@notifications.service.gov.uk",
            'dob_day': randint(1, 28),
            'dob_month': randint(1, 12),
            'dob_year': randint(1930, 1990),
        }
        return person

    def open_login_page(self):
        self.client.get("{}/login".format(self.locust.host))

    def open_signup_page(self):
        self.client.get("{}/signup".format(self.locust.host))

    def sign_up(self):
        print("signing up for service...")
        self.client.find_element(By.ID, "email").send_keys(
            self.new_user_credentials["username"])
        self.client.find_element(By.ID, "email_confirm").send_keys(
            self.new_user_credentials["username"])
        self.client.find_element(By.ID, "password").send_keys(
            self.new_user_credentials["password"])
        self.client.find_element(By.ID, "password_confirm").send_keys(
            self.new_user_credentials["password"])
        self.client.find_element(By.ID, "terms").click()
        self.client.find_element(By.ID, "signin-form-submit").click()

    def get_bucket_objects(self):
        bucket_objects = None
        while bucket_objects is None:
            print("waiting for objects in bucket...")
            try:
                bucket_objects = self.aws_s3_client.list_objects(
                    Bucket=self.bucket_name,
                    MaxKeys=123,
                    Prefix='mailbox/caspertests@lpa.opg.service.justice.gov.uk/',
                )
                return bucket_objects["Contents"]
            except:
                pass

    def delete_bucket_object(self, object_key):
        response = self.aws_s3_client.delete_object(
            Bucket=self.bucket_name,
            Key=object_key
        )

    def get_activation_link(self, email_body):
        try:
            activation_code = re.findall(
                'confirm/.[a-zA-Z0-9]+', email_body)[0]
            print(activation_code)
            if activation_code != []:
                activation_link = "{}/signup/{}".format(
                    self.locust.host, activation_code)
                print(activation_link)
            else:
                activation_link = "delete"
        except:
            activation_link = "delete"

        return activation_link

    def activate_account(self):
        match = False
        while match is False:
            print("getting objects")
            bucket_objects = self.get_bucket_objects()
            for bucket_object in bucket_objects:
                bucket_object_key = bucket_object["Key"]

                email = self.aws_s3_resource.Object(
                    self.bucket_name, bucket_object_key)

                email_body = email.get()['Body'].read().decode('utf-8')

                if self.new_user_credentials["username"] in email_body:
                    activation_link = self.get_activation_link(email_body)
                    if activation_link != "delete":
                        self.client.get(activation_link)
                        print("account activated!")
                    self.delete_bucket_object(bucket_object_key)
                    match = True

    def log_in(self):
        self.client.find_element(By.ID, "email").send_keys(
            self.new_user_credentials['username'])
        self.client.find_element(By.ID, "password").send_keys(
            self.new_user_credentials['password'])
        self.client.find_element(By.ID, "signin-form-submit").click()

    def create_new_lpa(self):
        self.client.find_element(By.ID, "create-new-lpa").click()

    def enter_account_holder_details(self):
        self.enter_person_details(self.account_holder)

    def start_pfa_type_lpa(self):
        self.client.find_element(By.ID, "type-property-and-financial").click()
        self.client.find_element(By.NAME, "save").click()

    def add_donor_details(self):
        self.client.find_element(By.LINK_TEXT, "Add donor details").click()

        self.enter_person_details(self.donor)

        self.client.find_element(By.LINK_TEXT, "Save and continue").click()

    def when_lpa_can_be_used(self):
        self.client.find_element(By.ID, "when-now").click()
        self.client.find_element(By.NAME, "save").click()

    def add_an_attorney(self):
        self.client.find_element(By.LINK_TEXT, "Add an attorney").click()

    def add_another_attorney(self):
        self.client.find_element(By.LINK_TEXT, "Add an attorney").click()

    def enter_person_details(self, person):
        dropdown = self.client.find_element(By.ID, "name-title")
        dropdown.find_element(
            By.XPATH, "//option[. = '{}}']".format(person['title'])).click()
        self.client.find_element(
            By.CSS_SELECTOR, "option:nth-child(3)").click()
        self.client.find_element(
            By.ID, "name-first").send_keys(person['first_name'])
        self.client.find_element(
            By.ID, "name-last").send_keys(person['last_name'])

        if person["type"] in ["donor", "attorney", "replacement_attorney", "account_holder"]:
            self.client.find_element(
                By.ID, "dob-date-day").send_keys(person['dob_day'])
            self.client.find_element(
                By.ID, "dob-date-month").send_keys(person['dob_month'])
            self.client.find_element(
                By.ID, "dob-date-year").send_keys(person['dob_year'])

        if person["type"] in ["donor", "attorney"]:
            self.client.find_element(
                By.ID, "email-address").send_keys(person['email'])

        self.client.find_element(
            By.ID, "postcode-lookup").send_keys(person['postcode'])
        self.client.find_element(
            By.LINK_TEXT, "Enter address manually").click()
        self.client.find_element(
            By.ID, "address-address1").send_keys(person['address_1'])
        self.client.find_element(
            By.ID, "address-address2").send_keys(person['address_2'])
        self.client.find_element(
            By.ID, "address-address3").send_keys(person['address_3'])

        if person["type"] not in ["accounnt_holder"]:
            self.client.find_element(By.ID, "form-save").click()
        else:
            self.client.find_element(By.NAME, "save").click()

    def complete_attorney_section(self):
        self.client.find_element(By.LINK_TEXT, "Save and continue").click()

    def add_a_replacement_attorney(self):
        self.client.find_element(
            By.LINK_TEXT, "Add replacement attorney").click()

    def add_another_replacement_attorney(self):
        self.client.find_element(
            By.LINK_TEXT, "Add replacement attorney").click()

    def complete_replacement_attorney_section(self):
        self.client.find_element(By.NAME, "save").click()

    def add_a_certificate_provider(self):
        self.client.find_element(
            By.LINK_TEXT, "Add a certificate provider").click()

    def add_another_certificate_provider(self):
        self.client.find_element(
            By.LINK_TEXT, "Add a certificate provider").click()

    def complete_certificate_provider_section(self):
        self.client.find_element(By.LINK_TEXT, "Save and continue").click()

    def add_a_notified_person(self):
        self.client.find_element(
            By.LINK_TEXT, "Add a \'person to notify\'").click()

    def add_another_notified_person(self):
        self.client.find_element(
            By.LINK_TEXT, "Add another \'person to notify\'").click()

    def complete_notified_person_section(self):
        self.client.find_element(By.NAME, "save").click()

    def skip_preferences_and_instructions(self):
        self.client.find_element(By.CSS_SELECTOR, "summary").click()

    def add_preferences_and_instructions(self):
        self.client.find_element(By.CSS_SELECTOR, "summary").click()

        self.client.find_element(By.ID, "preferences").click()
        self.client.find_element(
            By.ID, "preferences").send_keys("I want preferences")

        self.client.find_element(By.ID, "instruction").click()
        self.client.find_element(By.ID, "instruction").send_keys(
            "I want instructinos")
        self.client.find_element(By.NAME, "save").click()

    def apply_to_register_donor(self):
        self.client.find_element(By.ID, "whoIsRegistering-donor").click()
        self.client.find_element(By.NAME, "save").click()
        self.client.find_element(By.ID, "who-donor").click()
        self.client.find_element(By.NAME, "save").click()
        self.client.find_element(By.ID, "isRepeatApplication-is-new").click()
        self.client.find_element(By.NAME, "save").click()
        self.client.find_element(By.ID, "notApply").click()
        self.client.find_element(By.NAME, "save").click()

    def payment_by_cheque(self):
        self.client.find_element(
            By.LINK_TEXT, "Confirm and pay by cheque").click()

    def sign_out(self):
        self.client.find_element(By.LINK_TEXT, "Your LPAs").click()
        self.client.find_element(By.LINK_TEXT, "Sign out").click()
        self.client.close()

    def run_init(self):
        self.refuse_protected()
        self.define_actors()

    def create_user(self):
        self.create_user_credentials()
        self.open_signup_page()
        self.sign_up()
        self.activate_account()
        self.open_login_page()
        self.log_in()
        self.enter_account_holder_details()

    def existing_account_journey(self):
        self.open_login_page()
        self.log_in()

    def new_account_journey(self):
        self.start_pfa_type_lpa()
        self.add_donor_details()
        self.when_lpa_can_be_used()
        self.add_an_attorney()
        self.enter_person_details(self.attorney_1)
        # self.add_another_attorney()
        # self.enter_person_details(self.attorney_2)
        self.complete_attorney_section()
        self.add_a_replacement_attorney()
        self.enter_person_details(self.replacement_attorney_1)
        # self.add_another_replacement_attorney()
        # self.enter_person_details(self.replacement_attorney_2)
        self.complete_replacement_attorney_section()
        self.enter_person_details(self.certificate_provider_1)
        # self.add_another_certificate_provider()
        # self.enter_person_details(self.certificate_provider_2)
        self.complete_certificate_provider_section()
        self.add_a_notified_person()
        self.enter_person_details(self.named_person_1)
        self.add_another_notified_person()
        self.enter_person_details(self.named_person_2)
        self.complete_notified_person_section()
        # self.skip_preferences_and_instructions()
        self.add_preferences_and_instructions()
        self.apply_to_register_donor()
        self.payment_by_cheque()
        self.sign_out()

    def front_journey_donor(self):
        self.run_init()
        self.create_user()
        # self.new_account_journey()

    @task(1)
    def user_journey_public_front(self):
        self.get_s3_client()
        self.client.timed_event_for_locust(
            "Go to", "start page", self.front_journey_donor)


class LocustUser(FirefoxLocust):

    timeout = 20
    min_wait = 1
    max_wait = 10
    screen_width = 1200
    screen_height = 1200
    task_set = LocustUserBehavior
