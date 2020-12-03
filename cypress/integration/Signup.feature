Feature: Signup
 
  I want to be able to sign up
  
  @focus
  Scenario: Visit guidance
    Given I visit "/signup"
    Then I see "Create an account" in the title
    And I sign up standard test user
    #And I type "cypress_blah_test_user@digital.justice.gov.uk" into "input#email.form-control"
