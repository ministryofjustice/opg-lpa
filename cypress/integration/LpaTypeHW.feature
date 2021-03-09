@CreateLpa
Feature: Health and Welfare LPA starting from the Type page

    I want to go to the type page and create a Health and Welfare LPA

    Background:
        Given I ignore application exceptions

    @focus, @CleanupFixtures
    Scenario: Choose Health and Welfare as Lpa Type
        Given I log in as appropriate test user
        And If I am on dashboard I visit the type page
        When I choose Health and Welfare
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers health and welfare" in the page text
