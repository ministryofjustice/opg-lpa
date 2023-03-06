Feature: Accessible headings and information elements in the LPA workflow

    Background:
        Given I ignore application exceptions

    Scenario: Viewing instructions while navigating LPA creation screens
        Given I log in as appropriate test user
        And If I am on dashboard I click to create lpa
        And I am taken to the lpa type page
        Then the instructions expandable element should not be present
        When I choose Property and Finance
        And I click "save"
        And I am taken to the donor page
        Then the instructions expandable element should be present and closed
