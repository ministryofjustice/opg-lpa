Feature: Create LPA button on dashboard

    I want to be able to create an LPA from a button on the dashboard

    @focus
    Scenario: Dashboard Create button
        When I log in as appropriate test user
        And If I am on dashboard I click to create lpa
        Then I am taken to the lpa type page
