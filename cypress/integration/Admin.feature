Feature: Admin 
 
  I want to be able to visit the admin page and log in
  
  @focus
  Scenario: Visit admin page 
    Given I visit the admin sign-in page
    Then I see "Sign In" in the title
