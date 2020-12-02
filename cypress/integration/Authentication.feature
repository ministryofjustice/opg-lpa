Feature: Basic Login
 
  I want to log on to Make an LPA Service
  
  @focus
  Scenario: Logging into Make an LPA
    Given I visit "/login"
    Then I see "Sign in" in the title
    When I log in 
    Then I see "Your LPAs" in the title
  
  @focus
  Scenario: Visit homepage
    Given I visit "/"
    Then I see "Make a lasting power of attorney" in the title
