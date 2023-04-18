from common.helper import get_csrf_token
import time
from faker import Faker
from bs4 import BeautifulSoup
from common import PASSWORD
from common.config import logger

fake = Faker(["en_GB"])


class User:
    def __init__(self, client, email_address):
        self.client = client
        self.account_details = {
            "title": fake.prefix(),
            "first_name": fake.first_name(),
            "last_name": fake.last_name(),
            "address1": fake.street_address().title(),
            "address2": fake.city(),
            "address3": fake.administrative_unit(),
            "postcode": fake.postcode(),
            "dob_day": fake.day_of_month(),
            "dob_month": fake.month(),
            "dob_year": fake.year(),
            "email": email_address,
        }

        logger.debug("Initialising user with details: %s", self.account_details)

    def login(self):
        data = {"email": self.account_details["email"], "password": PASSWORD}
        response = self.client.post("/login", data=data)

        if self.client.cookies.get("lpa2") is not None and response.status_code == 200:
            logger.info(
                "%s %s %s logged in successfully using email address: %s",
                self.account_details["title"],
                self.account_details["first_name"],
                self.account_details["last_name"],
                self.account_details["email"],
            )

    def create(self):
        csrf_token_name, csrf_token = get_csrf_token(self.client, "/signup")

        data = {
            csrf_token_name: csrf_token,
            "email": self.account_details["email"],
            "email_confirm": self.account_details["email"],
            "password": PASSWORD,
            "password_confirm": PASSWORD,
            "submit": "Create account",
            "skip_confirm_password": "0",
            "terms": "1",
        }

        response = self.client.post("/signup", data=data)

        if response.status_code == 200:
            logger.info(
                "%s %s %s signed up successfully using email address %s",
                self.account_details["title"],
                self.account_details["first_name"],
                self.account_details["last_name"],
                self.account_details["email"],
            )

    def activate(self, email_address_id):
        email_received = False
        logger.debug("Waiting for email to be received for %s", email_address_id)
        while not email_received:
            try:
                with open(
                    "../cypress/activation_emails/" + email_address_id + ".activation"
                ) as f:
                    email = f.read()
                    activation_link = email.split(",")[1]
                    self.client.get(
                        activation_link, name="/signup/confirm/[activation_code]"
                    )
                    email_received = True
            except FileNotFoundError:
                logger.debug("Email not received yet for %s", email_address_id)
                time.sleep(1)
                pass

    def update_profile(self):
        csrf_token_name, csrf_token = get_csrf_token(self.client, "/user/about-you/new")

        data = {
            csrf_token_name: csrf_token,
            "name-title": self.account_details["title"],
            "name-first": self.account_details["first_name"],
            "name-last": self.account_details["last_name"],
            "address-address1": self.account_details["address1"],
            "address-address2": self.account_details["address2"],
            "address-address3": self.account_details["address3"],
            "address-postcode": self.account_details["postcode"],
            "dob-date[day]": self.account_details["dob_day"],
            "dob-date[month]": self.account_details["dob_month"],
            "dob-date[year]": self.account_details["dob_year"],
            "save": "Save and continue",
        }

        response = self.client.post(
            "/user/about-you/new", data=data, allow_redirects=False
        )

        if response.status_code == 302 and str(response.headers["Location"]).endswith(
            "/user/dashboard"
        ):
            logger.debug(
                "%s %s %s completed about you successfully for account %s",
                self.account_details["title"],
                self.account_details["first_name"],
                self.account_details["last_name"],
                self.account_details["email"],
            )
        else:
            logger.warning(
                "%s %s %s could not complete about you for account %s. Status code returned: %s",
                self.account_details["title"],
                self.account_details["first_name"],
                self.account_details["last_name"],
                self.account_details["email"],
                response.status_code,
            )

    def return_first_lpa_id(self):
        response = self.client.get("/user/dashboard")

        soup = BeautifulSoup(response.text, "html.parser")
        lists = soup.findAll("li")
        for list in lists:
            if list.has_attr("data-refresh-id"):
                return list["data-refresh-id"]

    def get_all_lpa_ids(self, page=1):
        response = self.client.get(
            "/user/dashboard/page/" + str(page), name="/user/dashboard/page/[page]"
        )

        soup = BeautifulSoup(response.text, "html.parser")
        lists = soup.findAll("li")
        lpa_ids = []
        for list in lists:
            if list.has_attr("data-refresh-id"):
                lpa_ids.append(list["data-refresh-id"])

        is_next_page = soup.find("a", {"class": "pager-next"})

        if is_next_page:
            page += 1
            lpa_ids.extend(self.get_all_lpa_ids(page))

        return lpa_ids

    def get_first_incomplete_lpa_id(self):
        response = self.client.get("/user/dashboard")

        soup = BeautifulSoup(response.text, "html.parser")
        lists = soup.find(
            "li", attrs={"class": "list-item list-item--v3 status-container--started"}
        )
        if lists:
            return lists["data-cy"][4:]

    def view_dashboard(self):
        response = self.client.get("/user/dashboard")

        if response.status_code == 200:
            logger.debug(
                "%s %s %s viewed dashboard successfully for account %s",
                self.account_details["title"],
                self.account_details["first_name"],
                self.account_details["last_name"],
                self.account_details["email"],
            )
        else:
            logger.warning(
                "%s %s %s could not view dashboard for account %s. Status code returned: %s",
                self.account_details["title"],
                self.account_details["first_name"],
                self.account_details["last_name"],
                self.account_details["email"],
                response.status_code,
            )
