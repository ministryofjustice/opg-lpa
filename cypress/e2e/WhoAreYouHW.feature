@PartOfStitchedRun
Feature: Who Are You for a Health and Welfare LPA

    I want to set Who Are You for a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent

    @focus @CleanupFixtures
    Scenario: Who Are You
        When I log in as appropriate test user
        And I visit the who are you page for the test fixture lpa
        Then I am taken to the who are you page
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

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
        And I can find hidden "other-input"
        When I click "save"
        Then I see "There is a problem" in the page text
        When I check "who"
        Then the page matches the "who-are-you" baseline image
        And I click "save"
        Then I am taken to the repeat application page
        When I click the last occurrence of "accordion-view-change"
        Then I am taken to the who are you page
        And I see "Thanks, you have already answered this question" in the page text
        When I click "continue"
        Then I am taken to the repeat application page
