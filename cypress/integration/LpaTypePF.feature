@CreateLpa
Feature: Type page Property and Finance LPA

    I want to go to the type page and create a Property and Finance LPA

    @focus
    Scenario: Choose Property and Finance as Lpa Type
        Given I log in as appropriate test user
        And If I am on dashboard I visit the type page
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text
