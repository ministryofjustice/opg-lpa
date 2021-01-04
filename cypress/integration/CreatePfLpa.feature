Feature: Create an LPA
 
  I want to create an LPA
  
  @focus
    Scenario: Create LPA
        # todo: during development, using seeded user saves time, but
        # to avoid tests having external dependency on seeded user,
        # the following should ultimately change to logging in with a
        # "seeded-by-code" newly signed up standard user
    Given I log in as seeded user
    Then I am taken to the type or dashboard page
    When I click button marked "Start now"
