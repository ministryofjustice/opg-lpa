Feature: Create a Health and Welfare LPA

    I want to create a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
 
    @focus
    Scenario: Create LPA with error first
        Given I log in as appropriate test user
        Then I visit the type page
        When I click "save"
        Then I see in the page text
            | There was a problem submitting the form |
            | You need to do the following: |
            | Choose a type of LPA |
        Then I choose Health and Welfare
        When I click "save"
        Then I am taken to the donor page

    @focus
    Scenario: Create LPA normal path
        Given I log in as appropriate test user
        Then I visit the type page
        Then I choose Health and Welfare
        When I click "save"
        Then I am taken to the donor page
