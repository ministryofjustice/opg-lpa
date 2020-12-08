Feature: Create an LPA
 
  I want to create an LPA
  
  @focus
    Scenario: Create LPA
        # TODO the following will change to logging in with signed up standard user
    Given I log in as seeded user
    And I am taken to "/user/dashboard"
    And I can find "Start now"
    #When I click button marked "Start now"
    #Then I am taken to "/lpa/type"
