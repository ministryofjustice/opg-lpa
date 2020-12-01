Feature: Homepage Links 
 
  I want to be able to follow the homepage links
  
  @focus
  Scenario: Visit guidance
    Given I visit "/home"
    Then I see "Make a lasting power of attorney" in the title
    And I can visit link containing "login"
    And I am taken to "/login"
    When I click back
    Then I can visit link containing "terms"
    Then I can visit link containing "privacy"
    #Then I can visit link containing "cookies"
    #And I can visit link named "a.js-guidance"

  @focus
  Scenario: Visit feedback
    Given I visit "/home"
    Then I see "Make a lasting power of attorney" in the title
    And I can visit link containing "feedback"
