Feature: Feedback
 
  I want to be able to provide feedback
  
  @focus
  Scenario: Visit feedback
    Given I visit "/home"
    And I ignore application exceptions
    And I visit link containing "feedback"
    Then I am taken to "/send-feedback"
    And I see "Send us feedback" in the title
    And I can find feedback buttons
    And I submit the feedback
    Then I see "There was a problem submitting your feedback" in the page text
