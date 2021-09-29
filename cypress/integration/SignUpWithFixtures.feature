@SignUpWithFixtures
Feature: SignUpWithFixtures

    I want to be able to sign up after fixtures have been put in place for testing

    Background:
        Given I ignore application exceptions

    @focus @CleanupFixtures
    Scenario: Sign up with an email address already belonging to a user account (LPAL-485)
        Given an existing user has the email "torrington.torponales@uat.digital.justice.gov.uk"
        When I sign up with email "torrington.torponales@uat.digital.justice.gov.uk" and password "Pass1234"
        Then I do not see "api-problem" in the page text
        And I do not see "There is a problem" in the page text
        And I see "Please check your email" in the page text
