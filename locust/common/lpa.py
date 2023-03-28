from common.helper import get_csrf_token
import random
import logging
from bs4 import BeautifulSoup
from faker import Faker
from common.config import logger

fake = Faker(["en_GB"])


class LastingPowerAttorney:
    def __init__(
        self,
        client,
        type="property-and-financial",
        account_details={},
        donor={},
        lpa_id=None,
    ):
        self.client = client
        self.type = type
        self.account_details = account_details
        self.donor = donor
        self.lpa_id = lpa_id

        self.num_of_attorneys_to_add = random.randint(1, 4)
        self.num_of_attorneys = 0

    def start_application(self):

        csrf_token_name, csrf_token = get_csrf_token(self.client, "/lpa/type")

        data = {
            csrf_token_name: csrf_token,
            "type": self.type,
            "save": "Save and continue",
        }

        response = self.client.post("/lpa/type", data=data, allow_redirects=False)

        if response.status_code == 302 and str(response.headers["Location"]).endswith(
            "/donor"
        ):
            self.lpa_id = response.headers["Location"].split("/")[2]
            logger.info(
                "User started application successfully with id: %s", self.lpa_id
            )
        else:
            logger.warning(
                "User could not start application. Status code returned: %s",
                response.status_code,
            )

    def add_donor_to_lpa(self):

        csrf_token_name, csrf_token = get_csrf_token(
            self.client, "/lpa/%s/donor/add" % self.lpa_id
        )
        data = {
            csrf_token_name: csrf_token,
            "name-title": fake.prefix(),
            "name-first": fake.first_name(),
            "name-last": fake.last_name(),
            "address-address1": fake.street_address().title(),
            "address-address2": fake.city(),
            "address-address3": fake.administrative_unit(),
            "address-postcode": fake.postcode(),
            "dob-date[day]": fake.day_of_month(),
            "dob-date[month]": fake.month(),
            "dob-date[year]": fake.year(),
            "email-address": fake.email(),
            "cannotSign": random.choice([0, 1]),
            "otherNames": fake.name() if random.choice([True, False, False]) else "",
            "save": "Save and continue",
        }

        response = self.client.post(
            "/lpa/%s/donor/add" % self.lpa_id, data=data, allow_redirects=False
        )

        if response.status_code == 302:
            logger.debug(
                "User %s added donor successfully to lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )
            self.donor = data
            return True
        else:
            logger.warning(
                "User %s could not add donor to lpa with id: %s. Status code returned: %s",
                self.account_details["email"],
                self.lpa_id,
                response.status_code,
            )

    def decide_when_lpa_starts(self):

        csrf_token_name, csrf_token = get_csrf_token(
            self.client, "/lpa/%s/when-lpa-starts" % self.lpa_id
        )

        data = {
            csrf_token_name: csrf_token,
            "when": random.choice(["now", "no-capacity"]),
            "save": "Save and continue",
        }

        response = self.client.post(
            "/lpa/%s/when-lpa-starts" % self.lpa_id, data=data, allow_redirects=False
        )

        if response.status_code == 302:
            logger.debug(
                "User %s decided when lpa starts successfully for lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )
            return True
        else:
            logger.info(
                "User %s failed to decide when lpa starts for lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )

    def life_sustaining_treatment(self):

        url = "/lpa/%s/life-sustaining" % self.lpa_id
        csrf_token_name, csrf_token = get_csrf_token(self.client, url)

        data = {
            csrf_token_name: csrf_token,
            "canSustainLife": random.randint(0, 1),
            "save": "Save and continue",
        }

        response = self.client.post(url, data=data, allow_redirects=False)

        if response.status_code == 302:
            logger.debug(
                "User %s decided life sustaining successfully for lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )
            return True
        else:
            logger.info(
                "User %s failed to decide life sustaining for lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )

    def add_primary_attorney_to_lpa(self):

        while self.num_of_attorneys < self.num_of_attorneys_to_add:
            csrf_token_name, csrf_token = get_csrf_token(
                self.client, "/lpa/%s/primary-attorney/add" % self.lpa_id
            )

            logger.debug(
                "User %s adding %s primary attorneys to lpa with id: %s",
                self.account_details["email"],
                self.num_of_attorneys,
                self.lpa_id,
            )

            data = {
                csrf_token_name: csrf_token,
                "name-title": fake.prefix(),
                "name-first": fake.first_name(),
                "name-last": fake.last_name(),
                "address-address1": fake.street_address().title(),
                "address-address2": fake.city(),
                "address-address3": fake.administrative_unit(),
                "address-postcode": fake.postcode(),
                "dob-date[day]": fake.day_of_month(),
                "dob-date[month]": fake.month(),
                "dob-date[year]": fake.year(),
                "email-address": fake.email(),
            }

            response = self.client.post(
                "/lpa/%s/primary-attorney/add" % self.lpa_id,
                data=data,
                allow_redirects=False,
            )

            if response.status_code == 302:
                logger.debug(
                    "User %s added primary attorney successfully to lpa with id: %s",
                    self.account_details["email"],
                    self.lpa_id,
                )
                self.num_of_attorneys += 1

    def decide_jointly_and_severally(self):

        csrf_token_name, csrf_token = get_csrf_token(
            self.client, "/lpa/%s/how-primary-attorneys-make-decision" % self.lpa_id
        )

        choice = random.choice(["jointly", "jointly-attorney-severally", "depends"])
        data = {
            csrf_token_name: csrf_token,
            "how": choice,
            "howDetails": fake.text() if choice == "depends" else "",
            "save": "Save and continue",
        }

        response = self.client.post(
            "/lpa/%s/how-primary-attorneys-make-decision" % self.lpa_id,
            data=data,
            allow_redirects=False,
        )

        if response.status_code == 302:
            logger.debug(
                "User %s decided  for lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )
        else:
            logger.info(
                "User %s failed to decide for lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )

    def add_replacement_attorney_to_lpa(self):

        csrf_token_name, csrf_token = get_csrf_token(
            self.client, "/lpa/%s/replacement-attorney" % self.lpa_id
        )

        add_replacement_attorneys = random.choice([True, False, False])

        if add_replacement_attorneys:
            add_attorney_csrf_token_name, add_attorney_csrf_token = get_csrf_token(
                self.client, "/lpa/%s/replacement-attorney/add" % self.lpa_id
            )

            logger.info(
                "User %s adding replacement attorneys to lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )
            add_attorney_data = {
                add_attorney_csrf_token_name: add_attorney_csrf_token,
                "name-title": fake.prefix(),
                "name-first": fake.first_name(),
                "name-last": fake.last_name(),
                "address-address1": fake.street_address().title(),
                "address-address2": fake.city(),
                "address-address3": fake.administrative_unit(),
                "address-postcode": fake.postcode(),
                "dob-date[day]": fake.day_of_month(),
                "dob-date[month]": fake.month(),
                "dob-date[year]": fake.year(),
            }
            self.client.post(
                "/lpa/%s/replacement-attorney/add" % self.lpa_id,
                data=add_attorney_data,
                allow_redirects=False,
            )
        else:
            logger.debug(
                "User %s not adding replacement attorneys to lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )

        data = {csrf_token_name: csrf_token, "save": "Save and continue"}

        response = self.client.post(
            "/lpa/%s/replacement-attorney" % self.lpa_id,
            data=data,
            allow_redirects=False,
        )

        if response.status_code == 302:
            logger.debug(
                "User %s completed add replacement attorneys step for lpa with id: %s. Choice to add replacements was %s",
                self.account_details["email"],
                self.lpa_id,
                add_replacement_attorneys,
            )
            return add_replacement_attorneys

    def decide_when_replacement_attorneys_used(self):

        csrf_token_name, csrf_token = get_csrf_token(
            self.client, "/lpa/%s/when-replacement-attorney-step-in" % self.lpa_id
        )

        choice = random.choice(["first", "depends", "last"])

        data = {
            csrf_token_name: csrf_token,
            "when": choice,
            "whenDetails": fake.text() if choice == "depends" else "",
            "save": "Save and continue",
        }

        self.client.post(
            "/lpa/%s/when-replacement-attorney-step-in" % self.lpa_id,
            data=data,
            allow_redirects=False,
        )
        logger.debug(
            "User %s decided  for lpa with id: %s",
            self.account_details["email"],
            self.lpa_id,
        )

    def add_certificate_provider_to_lpa(self):

        csrf_token_name, csrf_token = get_csrf_token(
            self.client, "/lpa/%s/certificate-provider/add" % self.lpa_id
        )
        data = {
            csrf_token_name: csrf_token,
            "name-title": fake.prefix(),
            "name-first": fake.first_name(),
            "name-last": fake.last_name(),
            "address-address1": fake.street_address().title(),
            "address-address2": fake.city(),
            "address-address3": fake.administrative_unit(),
            "address-postcode": fake.postcode(),
        }

        response = self.client.post(
            "/lpa/%s/certificate-provider/add" % self.lpa_id,
            data=data,
            allow_redirects=False,
        )

        if response.status_code == 302:
            logger.info(
                "User %s added certificate provider successfully to lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )
            self.add_people_to_notify_to_lpa()

    def add_people_to_notify_to_lpa(self):
        csrf_token_name, csrf_token = get_csrf_token(
            self.client, "/lpa/%s/people-to-notify" % self.lpa_id
        )
        data = {
            csrf_token_name: csrf_token,
            "save": "Save and continue",
        }

        response = self.client.post(
            "/lpa/%s/people-to-notify" % self.lpa_id, data=data, allow_redirects=False
        )

        if response.status_code == 302:
            logger.debug(
                "User %s added people to notify successfully to lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )

    def add_instructions_to_attorneys_to_lpa(self):

        csrf_token_name, csrf_token = get_csrf_token(
            self.client, "/lpa/%s/instructions" % self.lpa_id
        )

        data = {
            csrf_token_name: csrf_token,
            "instruction": fake.text(),
            "preference": fake.text(),
            "save": "Save and continue",
        }

        response = self.client.post(
            "/lpa/%s/instructions" % self.lpa_id, data=data, allow_redirects=False
        )

        if response.status_code == 302:
            logger.debug(
                "User %s added instructions to attorneys successfully to lpa with id: %s",
                self.account_details["email"],
                self.lpa_id,
            )

    def select_applicant_to_lpa(self):

        url = "/lpa/%s/applicant" % self.lpa_id
        csrf_token_name, csrf_token = get_csrf_token(self.client, url)

        data = {
            csrf_token_name: csrf_token,
            "whoIsRegistering": "donor",
            "save": "Save and continue",
        }

        response = self.client.post(url, data=data, allow_redirects=False)

        if response.status_code == 302:
            logger.debug(
                "User %s POSTed to %s and got redirected to %s",
                self.account_details["email"],
                url,
                response.headers["Location"],
            )

    def choose_correspondence_options(self):

        url = "/lpa/%s/correspondent" % self.lpa_id
        csrf_token_name, csrf_token = get_csrf_token(self.client, url)

        data = {
            csrf_token_name: csrf_token,
            "contactInWelsh": "0",
            "correspondence[contactByEmail]": "0",
            "correspondence[contactByPhone]": "0",
            "correspondence[contactByPost]": "0,1",
            "correspondence[email-address]": "",
            "correspondence[phone-number]": "",
            "save": "Save and continue",
        }

        response = self.client.post(url, data=data, allow_redirects=False)
        if response.status_code == 302:
            logger.info(
                "User %s POSTed to %s and got redirected to %s",
                self.account_details["email"],
                url,
                response.headers["Location"],
            )

    def who_are_you(self):

        url = "/lpa/%s/who-are-you" % self.lpa_id
        csrf_token_name, csrf_token = get_csrf_token(self.client, url)

        data = {
            csrf_token_name: csrf_token,
            "who": "notSaid",
            "save": "Save and continue",
            "other": "",
        }

        response = self.client.post(url, data=data, allow_redirects=False)
        if response.status_code == 302:
            logger.debug(
                "User %s POSTed to %s and got redirected to %s",
                self.account_details["email"],
                url,
                response.headers["Location"],
            )

    def confirm_repeat_application(self):

        url = "/lpa/%s/repeat-application" % self.lpa_id
        csrf_token_name, csrf_token = get_csrf_token(self.client, url)

        data = {
            csrf_token_name: csrf_token,
            "isRepeatApplication": "is-new",
            "repeatCaseNumber": "",
            "save": "Save and continue",
        }

        response = self.client.post(url, data=data, allow_redirects=False)
        if response.status_code == 302:
            logger.info(
                "User %s POSTed to %s and got redirected to %s",
                self.account_details["email"],
                url,
                response.headers["Location"],
            )

    def add_fee_reductions(self):

        url = "/lpa/%s/fee-reduction" % self.lpa_id
        csrf_token_name, csrf_token = get_csrf_token(self.client, url)

        data = {
            csrf_token_name: csrf_token,
            "reductionOptions": "notApply",
            "save": "Save and continue",
        }

        response = self.client.post(url, data=data, allow_redirects=False)
        if response.status_code == 302:
            logger.info(
                "User %s POSTed to %s and got redirected to %s",
                self.account_details["email"],
                url,
                response.headers["Location"],
            )

    def view_checkout_page(self, pay_now=True):

        self.client.get("/lpa/%s/checkout" % self.lpa_id, allow_redirects=False)

        if pay_now:
            payment_functions = [self.pay_by_cheque, self.pay_by_card]
            random.choice(payment_functions)()

    def pay_by_cheque(self):

        self.client.get("/lpa/%s/checkout/cheque" % self.lpa_id, allow_redirects=False)

        logger.debug(
            "User %s is paying by cheque for lpa with id: %s",
            self.account_details["email"],
            self.lpa_id,
        )

    def pay_by_card(self):
        # This function is a bit of a mess, but it works. It would be nice to refactor it at some point.

        card_number = "4444333322221111"
        expiry_month = "01"
        expiry_year = "30"
        cvc = "123"
        card_payments_url = "https://card.payments.service.gov.uk"

        url = "/lpa/%s/checkout" % self.lpa_id

        csrf_token_name, csrf_token = get_csrf_token(self.client, url)

        data = {
            csrf_token_name: csrf_token,
        }

        response = self.client.post("%s/pay" % url, data=data, allow_redirects=True)

        logger.debug(
            "User %s is paying by card for lpa with id: %s",
            self.account_details["email"],
            self.lpa_id,
        )

        pay_url = response.url
        # e.g. https://card.payments.service.gov.uk/card_details/l3duies6q4oso85bjt2gu270l4

        response = self.client.get(pay_url, allow_redirects=True)

        soup = BeautifulSoup(response.text, "html.parser")
        csrf_token_tag = soup.find(
            "input", attrs={"name": "csrfToken", "type": "hidden"}
        )
        csrf_token = csrf_token_tag["value"]
        # e.g. v7wtfMjA-DfHZimB4Dxfv5np7bSir11SNdCI
        payment_id = pay_url.split("/")[-1]
        # e.g. l3duies6q4oso85bjt2gu270l4

        response = self.client.post(
            "%s/check_card/%s" % (card_payments_url, payment_id),
            data={"cardNo": card_number},
            allow_redirects=True,
        )

        logger.debug(
            "User %s POSTed to %s and got redirected to %s",
            self.account_details["email"],
            url,
            response.url,
        )

        data = {
            "chargeId": payment_id,
            "csrfToken": csrf_token,
            "cardNo": card_number,
            "expiryMonth": expiry_month,
            "expiryYear": expiry_year,
            "cardholderName": self.account_details["title"]
            + " "
            + self.account_details["first_name"]
            + " "
            + self.account_details["last_name"],
            "cvc": cvc,
            "addressCountry": "GB",
            "addressLine1": self.account_details["address1"],
            "addressLine2": self.account_details["address2"],
            "addressCity": self.account_details["address3"],
            "addressPostcode": self.account_details["postcode"],
            "email": "simulate-delivered@notifications.service.gov.uk",
        }

        response = self.client.post(
            "%s/card_details/%s" % (card_payments_url, payment_id),
            data=data,
            allow_redirects=True,
        )

        response = self.client.get(
            "%s/card_details/%s/confirm" % (card_payments_url, payment_id),
            allow_redirects=True,
        )
        soup = BeautifulSoup(response.text, "html.parser")
        csrf_token_tag = soup.find(
            "input", attrs={"name": "csrfToken", "type": "hidden"}
        )
        csrf_token = csrf_token_tag["value"]

        data = {
            "chargeId": payment_id,
            "csrfToken": csrf_token,
        }

        response = self.client.post(
            "%s/card_details/%s/confirm" % (card_payments_url, payment_id),
            data=data,
            allow_redirects=True,
        )

    def generate_lpa_pdf(self):
        url = "/lpa/%s/download/lp1" % self.lpa_id

        response = self.client.get(url, allow_redirects=False)

        logger.debug("Generating LPA PDF for lpa with id: %s", self.lpa_id)

    def download_lpa_pdf(self):
        url = "/lpa/%s/download/lp1" % self.lpa_id

        response = self.client.get(url, allow_redirects=True)

        logger.debug("Downloaded LPA PDF for lpa with id: %s", self.lpa_id)

    def delete_lpa(self):
        response = self.client.get(
            "/user/dashboard/delete-lpa/%s" % self.lpa_id, allow_redirects=True
        )
        logger.debug("Deleted LPA with id: %s", self.lpa_id)

    def view_lpa_status(self):
        response = self.client.get("/lpa/%s/status" % self.lpa_id, allow_redirects=True)
