Feature: Signup
 
  I want to be able to sign up
  
  @focus
  Scenario: Sign up with automatically generated test username and password
    Given I sign up standard test user
    Then I see "Please check your email" in the title
    And I see standard test user in the page text
    And I receive email
    And I see "Account activated" in the title
