Feature: Signup
 
    I want to be able to sign up

    Background:
        Given I ignore application exceptions
  
    @focus
    Scenario: Sign up with automatically generated test username and password
        Given I sign up standard test user
        Then I see "Please check your email" in the title
        And I see standard test user in the page text
        And I receive email
        And I see "Account activated" in the title
        Then I log in as standard test user
        And I see "Your details" in the title
        Then I submit About Me details
        Then I see "There was a problem" in the page text
