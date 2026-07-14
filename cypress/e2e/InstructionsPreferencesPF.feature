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
            | instruction | aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa |
            | preferences | Some preferences |
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | No single word in your instructions can be more than 85 characters long |
        When I fill out
            | instruction | Some instructions |
            | preferences | bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb |
        And I click "save"
        Then I see in the page text
            | There is a problem |
            | No single word in your preferences can be more than 85 characters long |
        When I fill out
            | instruction | See http://example.com for details |
            | preferences | Some preferences |
        And I click "save"
        Then I see in the page text
            | There is a problem |
            | Web links (http:// and https://) are not allowed in instructions |
        When I fill out
            | instruction | Some instructions |
            | preferences | See https://example.com for details |
        And I click "save"
        Then I see in the page text
            | There is a problem |
            | Web links (http:// and https://) are not allowed in preferences |
        When I fill out
            | instruction | Some instructions |
            | preferences | Some preferences |
        And I click "save"
        Then I am taken to the applicant page
        When I click the last occurrence of "accordion-view-change"
        Then I see in the page text
            | Some instructions |
            | Some preferences |
        When I click "save"
        Then I am taken to the applicant page
        When I visit link containing "preview the LPA"

