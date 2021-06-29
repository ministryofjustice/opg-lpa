@CreateLpa
Feature: Add a correspondent to a Property and Finance LPA

    I want to add a correspondent to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences, applicant

    @focus, @CleanupFixtures
    Scenario: Create LPA normal path
        When I log in as appropriate test user
        And I visit the correspondent page for the test fixture lpa
        Then I am taken to the correspondent page
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        And I can find "change-correspondent" with data-inited
        When I click "change-correspondent"
        Then I can find "form-reuse-details"
        And I see "Which details would you like to reuse?" in the page text
        # donor is correspondent as default
        # choose attorney as correspondent
        When I check "reuse-details-2"
        And I click "continue"
        Then I can find "form-correspondent"
        And I click "form-save"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-correspondent"
        And I see "Standard Trust" in the page text
        When I click "save"
        Then I am taken to the who are you page
