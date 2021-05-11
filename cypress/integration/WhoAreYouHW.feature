@CreateLpa
Feature: Who Are You for a Health and Welfare LPA

    I want to set Who Are You for a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant

    @focus
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
        When I click "save"
        Then I see "There is a problem" in the page text
        When I check "who"
        And I click "save"
        Then I am taken to the repeat application page
        When I click occurrence 12 of "accordion-view-change"
        Then I am taken to the who are you page
        And I see "Thanks, you have already answered this question" in the page text
        When I click "continue"
        Then I am taken to the repeat application page
        # repeatCaseNumber should be hidden initially
        And I can find hidden "repeatCaseNumber"

        When I check "isRepeatApplication-is-repeat"
        And I click "save"
        When I click element marked "Confirm and continue"
        Then I see in the page text
            | There is a problem |
            | If you are making a repeat application, you need to enter the case number given to you by the Office of the Public Guardian. | 

        # for PF we test typing in a case number. The other scenario where this is not a repeat, is covered here
        When I check "isRepeatApplication-is-new"
        And I click "save"
        Then I am taken to the fee reduction page
