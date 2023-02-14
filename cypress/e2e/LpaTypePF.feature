@PartOfStitchedRun
Feature: Property and Finance LPA starting from the Type page

    I want to go to the type page and create a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I log in as appropriate test user
        And If I am on dashboard I visit the type page

    @CleanupFixtures
    Scenario: Create LPA with error first
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose a type of LPA |
        And I see "Error" in the title
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text
        When I click "add-donor"
        Then I can find "form-donor"

    @CleanupFixtures @EncodingCheckAfter @LinkCheckAfter
    Scenario: Choose Property and Finance as Lpa Type
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text
