Feature: Feedback
 
  I want to be able to provide feedback
  
  @focus
  Scenario: Visit feedback
    Given I visit "/home"
    And I visit link containing "feedback"
    Then I am taken to "/send-feedback"
    And I see "Send us feedback" in the title
