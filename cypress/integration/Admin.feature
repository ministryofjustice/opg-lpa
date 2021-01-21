Feature: Admin 
 
  I want to be able to visit the admin page and log in
  
  @focus
  Scenario: Visit admin page 
    Given I visit the admin page
    Then I see "Make a lasting power of attorney" in the title
