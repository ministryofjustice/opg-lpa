@Password
Feature: Password

    I want to be able to change my password

    @focus
    Scenario: Change password with mismatched details
      Given I ignore application exceptions
      When I log in as seeded user
      And I visit link containing "Your details"
      Then I am taken to "/user/about-you"
      When I visit link containing "Change Password"
      Then I am taken to "/user/change-password"
      When I try to change password with a mismatch
      Then I am taken to "/user/change-password"
      And I see in the page text
          | There is a problem |
          | Enter matching passwords |
      And I see "Error" in the title

    @focus
    Scenario: Change existing password with invalid details
      Given I ignore application exceptions
      When I log in as seeded user
      And I visit link containing "Your details"
      Then I am taken to "/user/about-you"
      When I visit link containing "Change Password"
      Then I am taken to "/user/change-password"
      When I try to change password to an invalid one
      Then I am taken to "/user/change-password"
      And I see in the page text
          | There is a problem |
          | Choose a new password that includes at least one digit (0-9) |
          | Choose a new password that includes at least one lower case letter (a-z) |
          | Choose a new password that includes at least one capital letter (A-Z) |
          | Enter matching passwords |
      And I see "Error" in the title
