@PartOfStitchedRun
Feature: Repeat Application for a Property and Finance LPA

    I want to set Repeat Application for a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you

    @focus @CleanupFixtures
    Scenario: Repeat Application
        When I log in as appropriate test user
        And I visit the repeat application page for the test fixture lpa
        Then I am taken to the repeat application page
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        # repeatCaseNumber should be hidden initially
        And I can find hidden "repeatCaseNumber"

        When I check "isRepeatApplication-is-repeat"
        And I click "save"
        When I click element marked "Confirm and continue"
        Then I see in the page text
            | There is a problem |
            | If you are making a repeat application, you need to enter the case number given to you by the Office of the Public Guardian. | 
        When I type "12345678910121213" into "repeatCaseNumber"
        And I click "save"
        When I click element marked "Confirm and continue"
        Then I see in the page text
            | There is a problem |
            | Case Number must be twelve letters or fewer |
            # for PF we test typing in a case number. The other scenario where this is not a repeat, is covered in HW feature
        When I clear the value in "repeatCaseNumber"
        And I type "12345678" into "repeatCaseNumber"
        And I click "save"
        Then I can see popup
        When I click element marked "Confirm and continue"
        Then I am taken to the fee reduction page
