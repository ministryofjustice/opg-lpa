Feature: Create an LPA

    I want to create an LPA

    Background:
        Given I ignore application exceptions
 
    @focus
    Scenario: Dashboard has Link to Type page
        # we use seeded user here because a newly signed-up user would not yet have a dashboard page
        Given I log in as seeded user
        When I click "createnewlpa"
        Then I am taken to the lpa type page
  
    @focus
    Scenario: Create LPA
        # todo: to avoid tests having external dependency on seeded user,
        # the following may ultimately change to logging in with a
        # "seeded-by-code" newly signed up standard user
        Given I log in as appropriate test user
        Then I visit the type page
        When I click "save"
        And I see in the page text
            | There was a problem submitting the form |
            | You need to do the following: |
            | Choose a type of LPA |
