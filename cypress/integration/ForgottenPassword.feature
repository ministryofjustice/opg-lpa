Feature: Homepage 
 
  I want to be able to visit the homepage and follow the links
  
  @focus
  Scenario: Visit home page links
    Given I visit "/login"
    When I visit link containing "Forgotten your password?"
    Then I am taken to "/forgot-password"
    And I see "Reset your password" in the title
    When I populate email fields with standard user address
    Then I see "We've emailed a link" in the page text
    And I see standard test user in the page text
    And I receive password reset email and can visit the link
    And I see "Account activated" in the title
