Feature: Homepage Links 
 
  I want to be able to follow the homepage links
  
  @focus
  Scenario: Visit guidance
    Given I visit "/home"
    Then I see "Make a lasting power of attorney" in the title
    And I visit link containing "login"
    And I am taken to "/login"
    When I click back
    Then I visit link in new tab containing "terms"
    Then I visit link in new tab containing "privacy"
    #Then I visit link containing "cookies"
    #And I visit link named "a.js-guidance"

  @focus
  Scenario: Visit feedback
    Given I visit "/home"
    Then I see "Make a lasting power of attorney" in the title
    And I visit link containing "feedback"
