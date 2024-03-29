from locust import HttpUser, task, between
import random
from common.user import User
from common.lpa import LastingPowerAttorney
from common.config import logger, DISABLE_SSL_VERIFY

# TODO: Add people to notify. Currently nobody is added to notify
# TODO: Handle being logged out during the user journey


class MakeAnLPAUser(HttpUser):
    wait_time = between(5, 30)

    def on_start(self):
        if DISABLE_SSL_VERIFY:
            logger.info("Disabling SSL verification")
            self.client.verify = False

        random_number = str(random.getrandbits(32))
        email_address = (
            "caspertests+" + random_number + "@lpa.opg.service.justice.gov.uk"
        )
        self.user = User(self.client, email_address)
        self.user.create()
        self.user.activate(random_number)
        self.user.login()
        self.user.update_profile()
        self.user.login()

    @task(8)
    def CompleteFullLPA(self):
        lpa_type = random.choice(["property-and-financial", "health-and-welfare"])
        lpa = LastingPowerAttorney(
            self.client, type=lpa_type, account_details=self.user.account_details
        )
        lpa.start_application()
        lpa.add_donor_to_lpa()
        if lpa.type == "property-and-financial":
            lpa.decide_when_lpa_starts()
        elif lpa.type == "health-and-welfare":
            lpa.life_sustaining_treatment()
        lpa.add_primary_attorney_to_lpa()
        if lpa.num_of_attorneys > 1:
            lpa.decide_jointly_and_severally()
        if lpa.add_replacement_attorney_to_lpa():
            lpa.decide_when_replacement_attorneys_used()
        lpa.add_certificate_provider_to_lpa()
        lpa.add_instructions_to_attorneys_to_lpa()
        lpa.select_applicant_to_lpa()
        lpa.choose_correspondence_options()
        lpa.who_are_you()
        lpa.confirm_repeat_application()
        lpa.add_fee_reductions()
        lpa.view_checkout_page(pay_now=True)
        lpa.generate_lpa_pdf()
        lpa.download_lpa_pdf()

        logger.info(
            "%s completed %s LPA with ID: %s",
            self.user.account_details["email"],
            lpa.type,
            lpa.lpa_id,
        )

    @task(1)
    def DeleteRandomLPA(self):
        lpa_ids = self.user.get_all_lpa_ids()
        if len(lpa_ids) == 0:
            return
        lpa_id = random.choice(self.user.get_all_lpa_ids())
        lpa = LastingPowerAttorney(self.client, lpa_id=lpa_id)
        lpa.delete_lpa()

    @task(5)
    def PrintLPA(self):
        lpa_ids = self.user.get_all_lpa_ids()
        if len(lpa_ids) == 0:
            return
        lpa_id = random.choice(self.user.get_all_lpa_ids())
        lpa = LastingPowerAttorney(self.client, lpa_id=lpa_id)
        lpa.generate_lpa_pdf()
        lpa.download_lpa_pdf()

    @task(3)
    def ViewLPA(self):
        lpa_ids = self.user.get_all_lpa_ids()
        if len(lpa_ids) == 0:
            return
        lpa_id = random.choice(self.user.get_all_lpa_ids())
        lpa = LastingPowerAttorney(self.client, lpa_id=lpa_id)
        lpa.view_lpa_status()

    @task
    def FinishIncompleteLPA(self):
        lpa_id = self.user.get_first_incomplete_lpa_id()
        if lpa_id:
            logger.debug("Completing incomplete LPA with ID: %s", lpa_id)
            lpa = LastingPowerAttorney(self.client, lpa_id=lpa_id)
            lpa.start_application()
            lpa.add_donor_to_lpa()
            if lpa.type == "property-and-financial":
                lpa.decide_when_lpa_starts()
            elif lpa.type == "health-and-welfare":
                lpa.life_sustaining_treatment()
            lpa.add_primary_attorney_to_lpa()
            if lpa.num_of_attorneys > 1:
                lpa.decide_jointly_and_severally()
            if lpa.add_replacement_attorney_to_lpa():
                lpa.decide_when_replacement_attorneys_used()
            lpa.add_certificate_provider_to_lpa()
            lpa.add_instructions_to_attorneys_to_lpa()
            lpa.select_applicant_to_lpa()
            lpa.choose_correspondence_options()
            lpa.who_are_you()
            lpa.confirm_repeat_application()
            lpa.add_fee_reductions()
            lpa.view_checkout_page(pay_now=True)
            lpa.generate_lpa_pdf()
            lpa.download_lpa_pdf()

    @task(3)
    def UpdateUserDetails(self):
        self.user.update_profile()

    @task(10)
    def ViewDashboard(self):
        self.user.view_dashboard()
