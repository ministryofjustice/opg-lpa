@SignupWithFixtures
Feature: SignUpWithFixtures

    I want to be able to sign up after fixtures have been put in place for testing

    Background:
        Given I ignore application exceptions

    @focus @CleanupFixtures
    Scenario: Sign up with an email address already belonging to a user account (LPAL-485)
        When I sign up with email "initially_inactive_seeded_test_user3@digital.justice.gov.uk" and password "Pass12345678"
        Then I do not see "api-problem" in the page text
        And I do not see "There is a problem" in the page text
        And I see "Please check your email" in the page text
