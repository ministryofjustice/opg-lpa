Feature: Create an LPA
 
  I want to create an LPA
    @focus
    Scenario: Dashboard has Link to Type page
        # we use seeded user here because a newly signed-up user would not yet have a dashboard page
        Given I log in as seeded user
        When I click "createnewlpa"
        Then I am taken to the type page
  
    @focus
    Scenario: Create LPA
        # todo: to avoid tests having external dependency on seeded user,
        # the following may ultimately change to logging in with a
        # "seeded-by-code" newly signed up standard user
        Given I log in as appropriate test user
        Then I visit the type page
        When I click submit
