Feature: Homepage 
 
    I want to be able to get a password reset email and follow the link

    # note that this requires Signup.feature to have run, to test resetting a password for an existing user
    # todo The password email sent when a user doesn't exist yet, has a different title,
    # isn't tested by Casper, and a test probably needs adding here
  
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
        When I choose a new password
        Then I am taken to the login page
        And I see "Password successfully reset" in the page text
        When I log in with new password
