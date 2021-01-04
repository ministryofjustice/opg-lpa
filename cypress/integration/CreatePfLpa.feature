Feature: Create an LPA
 
  I want to create an LPA
  
  @focus
    Scenario: Create LPA
        # TODO the following may change to logging in with signed up standard user
    Given I log in as seeded user
    Then I am taken to the type or dashboard page
    When I click button marked "Start now"
