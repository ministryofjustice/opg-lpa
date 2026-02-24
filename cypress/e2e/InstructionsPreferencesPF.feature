@PartOfStitchedRun
Feature: Specify Instructions and Preferences for a Property and Finance LPA

    I want to specify Instructions and Preferences for a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify

    @focus @CleanupFixtures
    Scenario: Specify Instructions and Preferences
        When I log in as appropriate test user
        And I visit the instructions page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        Then I can find "instruction" but it is not visible
        And I can find "preferences" but it is not visible
        When I click "add-extra-preferences"
        Then I can find "instruction" and it is visible
        And I can find "preferences" and it is visible
        And I fill out
            | instruction | Some instructions |
            | preferences | Some preferences |
        When I click "save"
        Then I am taken to the applicant page
        When I click the last occurrence of "accordion-view-change"
        Then I see in the page text
            | Some instructions |
            | Some preferences |
        When I click "save"
        Then I am taken to the applicant page
        When I visit link containing "preview the LPA"
