@PasswordReset @SignupIncluded
Feature: PasswordReset

    I want to be able to reset my password

    Scenario: Sign up with new user, reset password (LPAL-797)
      Given I sign up "PasswordResetUser" test user with password "Pass12345678"
      When I use activation email for "PasswordResetUser" to visit the link
      Then I see "Account activated" in the title

      When I log in as "PasswordResetUser" test user
      Then a simulated click on the "sign-out" link causes a 302 redirect to "https://www.gov.uk/done/lasting-power-of-attorney"

      When I visit the login page
      And I visit link containing "Forgotten your password?"
      Then I am taken to "/forgot-password"
      And I see "Reset your password" in the title

      When I populate email fields with "PasswordResetUser" user address
      Then I see "We've emailed a link" in the page text
      And I see "PasswordResetUser" user in the page text
      And I use password reset email for "PasswordResetUser" to visit the link

      # use a valid new password
      When I choose a new password
      Then I am taken to the login page
      And I see "Password successfully reset" in the page text

      # change password back to old one
      When I log in as "PasswordResetUser" with new password
      And I visit link containing "Your details"
      Then I am taken to "/user/about-you/new"

      # have to fill out personal details to enable changing password back
      When I select "Mr" on "name-title" with data-inited
      And I force fill out
        | name-first| Chris |
        | name-last| Smith |
        | dob-date-day| 1 |
        | dob-date-month| 12 |
        | dob-date-year| 1982 |
        | address-address1| 12 Highway Close |
        | address-postcode| PL45 9JA |
      And I click "save"
      Then I am taken to the lpa type page

      When I visit link containing "Your details"
      And I visit link containing "Change Password"
      Then I am taken to "/user/change-password"

      When I change password for "PasswordResetUser" back to my old one
      Then I see "Your new password has been saved" in the page text
