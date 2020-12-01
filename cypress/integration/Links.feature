Feature: Homepage Links
 
  I want to be able to follow the homepage links
  
  @focus
  Scenario: Visit homepage
    Given I visit "/home"
    Then I see "Make a lasting power of attorney" in the title
    And I can visit link containing "feedback"
