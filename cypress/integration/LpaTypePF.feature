@CreateLpa
Feature: Property and Finance LPA starting from the Type page

    I want to go to the type page and create a Property and Finance LPA

    Background:
        Given I ignore application exceptions

    @focus, @CleanupFixtures
    Scenario: Choose Property and Finance as Lpa Type
        Given I log in as appropriate test user
        And If I am on dashboard I visit the type page
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text
