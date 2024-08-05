@PartOfStitchedRun
Feature: Clone Property and Finance LPA starting from the Type page

    I want to go to the type page and clone a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And Ordnance Survey postcode lookup responses are stubbed out for good postcode B1 1TF
        And Ordnance Survey postcode lookup responses are stubbed out for good postcode NG2 1AR
        And I set cloned to true
        And I log in as second seeded user
        Then I am taken to the dashboard page

    @focus @CleanupFixtures
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
