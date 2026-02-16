@PartOfStitchedRun
Feature: Add a correspondent to a Health and Welfare LPA

    I want to add a correspondent to a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant

    @focus @CleanupFixtures
    Scenario: Add a correspondent
        When I log in as appropriate test user
        And I visit the correspondent page for the test fixture lpa
        Then I am taken to the correspondent page
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        Then the page matches the "add-correspondent" baseline image
        And I can find "contactInWelsh-0"
        And I can find "contactInWelsh-1"
        And I can find "contactByPhone"
        And I can find "contactByPost"
        And I can find hidden "phone-number"
        And I can find "change-correspondent" with data-inited
        When I click "change-correspondent"
        Then I can find "form-reuse-details"
        And I see "Which details would you like to reuse?" in the page text
        # donor is correspondent as default
        And I see "Mrs Nancy Garrison" in the page text
        And "contactByEmail" is checked
        And I can find "email-address" and it is visible
        # choose donor as correspondent
        When I check "reuse-details-1"
        And I click "continue"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-reuse-details"
        And I see "Mrs Nancy Garrison" in the page text
        And I click "save"
        Then I am taken to the who are you page
