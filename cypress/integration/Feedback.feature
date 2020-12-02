Feature: Feedback
 
  I want to be able to provide feedback
  
  @focus
  Scenario: Visit feedback
    Given I visit "/home"
    And I visit link containing "feedback"
