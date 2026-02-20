@PartOfStitchedRun
Feature: Add Applicant to a Health and Welfare LPA

    I want to add an Applicant to a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences

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
        # select the attorney as applicant
        When I check occurrence 1 of checkbox
        When I check occurrence 0 of radio button
        Then the page matches the "add-applicant" baseline image
        And I click "save"
        Then I am taken to the correspondent page
