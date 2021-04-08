@CreateLpa
Feature: Add a correspondent to a Health and Welfare LPA

    I want to add a correspondent to a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant

    @focus, @CleanupFixtures
    Scenario: Add a correspondent 
        When I log in as appropriate test user
        And I visit the correspondent page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove
        
        Then I am taken to the correspondent page
        And I can find "contactInWelsh-0"
        And I can find "contactInWelsh-1"
        When I click "change-correspondent"
        Then I can see popup
        And I see "Which details would you like to reuse?" in the page text
        # donor is correspondent as default
        And I see "Mrs Nancy Garrison" in the page text
        And "contactByEmail" is checked
        # choose donor as correspondent
        When I check "reuse-details-1"
        And I click "continue"
        Then I am taken to the correspondent page
        And I see "Mrs Nancy Garrison" in the page text
        And I click "save"
        Then I am taken to the who are you page
