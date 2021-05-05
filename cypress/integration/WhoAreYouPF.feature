@CreateLpa
Feature: Who Are You for a Property and Finance LPA

    I want to set Who Are You for a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant

    @focus, @CleanupFixtures
    Scenario: Who Are You
        When I log in as appropriate test user

        # THIS WILL GO -  fixture will need correspondent set
        And I visit the correspondent page for the test fixture lpa
        Then I am taken to the correspondent page
        When I click "save"
        Then I am taken to the who are you page
        # end of this will go

        # ultimately how it should be starts here
        And I visit the who are you page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        Then I am taken to the who are you page
        And I can find "who"
        And I can find "who-friend-or-family"
        And I can find "who-finance-professional"
        And I can find "who-legal-professional"
        And I can find "who-estate-planning-professional"
        And I can find "who-digital-partner"
        And I can find "who-charity"
        And I can find "who-organisation"
        And I can find "who-other"
        And I can find "who-notSaid"
