@CreateLpa
Feature: Add People to Notify to a Property and Finance LPA

    I want to add People to Notify to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider

    @focus @CleanupFixtures
    Scenario: Add person to notify
        When I log in as appropriate test user
        And I visit the people to notify page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        # note that for a clone, we do not actually add a person to notify, as this is already tested in PersonToNotifyPF.feature.
        When I click "add"
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-people-to-notify"
        When I click "form-cancel"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-people-to-notify" 
        And I click "save"
        Then I am taken to the instructions page
