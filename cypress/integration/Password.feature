Feature: Password 
 
    I want to be able to reset or change my password 

    # note that this requires Signup.feature to have run, to test resetting a password for an existing user
    # todo The password email sent when a user doesn't exist yet, has a different title,
    # isn't tested by Casper, and a test may need adding here
    
    @focus
    # this scenario replicates 35-ChangePassword from Casper
    # note that due to a bug in the system, this scenario can only be run before the forgot pasword scenario, not after
    Scenario: Change existing password with invalid details
        Given I ignore application exceptions
        When I log in as standard test user
        And I visit link containing "Your details"
        Then I am taken to "/user/about-you"
        When I visit link containing "Change Password"
        Then I am taken to "/user/change-password"
        When I try to change password to an invalid one
        Then I see "There was a problem changing your password" in the page text
  
    @focus
    Scenario: Reset Password using email link
        Given I visit the login page
        When I visit link containing "Forgotten your password?"
        Then I am taken to "/forgot-password"
        And I see "Reset your password" in the title
        When I populate email fields with standard test user address
        Then I see "We've emailed a link" in the page text
        And I see standard test user in the page text
        And I use password reset email to visit the link
        When I choose a new password
        Then I am taken to the login page
        And I see "Password successfully reset" in the page text
        When I log in with new password
        # accept either type or dashboard page , so we're agnostic as to what has previously happened to this user
        Then I am taken to the type or dashboard page
        # change password back to old one. This wasn't in the original casper tests, but ensures this feature doesn't have any side effects 
        When I visit link containing "Your details"
        Then I am taken to "/user/about-you"
        When I visit link containing "Change Password"
        Then I am taken to "/user/change-password"
        When I change password back to my old one
        And I see "Your new password has been saved" in the page text


