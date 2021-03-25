@CreateLpa
Feature: Specify Instructions and Preferences for a Property and Finance LPA

    I want to specify Instructions and Preferences for a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify

    @focus, @CleanupFixtures
    Scenario: Specify Instructions and Preferences
        When I log in as appropriate test user
        And I visit the instructions page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        When I click "add-extra-preferences"
        And I force fill out
            | instruction | Lorem Ipsum |
            | preferences | Neque porro quisquam |
        When I click "save"
        Then I am taken to the applicant page
        When I click occurrence 9 of "accordion-view-change"
        Then I see in the page text
            | Lorem Ipsum |
            | Neque porro quisquam |
        When I click "save"
        Then I am taken to the applicant page
        When I visit link containing "preview the LPA"

