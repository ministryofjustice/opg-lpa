@CreateLpa
Feature: Add Applicant to a Property and Finance LPA

    I want to add an Applicant to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences

    @focus @CleanupFixtures
    Scenario: Add Applicant
        When I log in as appropriate test user
        And I visit the applicant page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        Then I am taken to the applicant page
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Select the person who is applying to register the LPA |
        And I see "Error" in the title
        # select the donor as applicant
        When I check "whoIsRegistering-donor"
        And I click "save"
        Then I am taken to the correspondent page
