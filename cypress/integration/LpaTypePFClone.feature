@CreateLpa
Feature: Clone Property and Finance LPA starting from the Type page

    I want to go to the type page and clone a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I log in as appropriate test user
        Then I am taken to the dashboard page

    @CleanupFixtures
    Scenario: Create LPA with error first
        # we should find at least one existing lpa with related links. we simply click the first one we find , to clone it
        Then I can find "check-signing-dates"
        And I can find "delete-lpa"
        When I click occurrence 0 of "reuse-lpa-details"
        Then I am taken to the type page for cloned lpa
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
        Then I can see popup

    @focus, @CleanupFixtures
    Scenario: Choose Property and Finance as Lpa Type
        # we should find at least one existing lpa with related links. we simply click the first one we find , to clone it
        Then I can find "check-signing-dates"
        And I can find "delete-lpa"
        When I click occurrence 0 of "reuse-lpa-details"
        Then I am taken to the type page for cloned lpa
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text

