Feature: Authentication
 
  I want to log on to Make an LPA Service
  
  @focus
  Scenario: Logging into Make an LPA with existing seeded user that has some LPAs
    When I log in as seeded user
    Then I see "Your LPAs" in the title
