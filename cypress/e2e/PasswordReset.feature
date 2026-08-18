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
      Then I am returned to the appropriate page shown after a password reset
      And I see "Password successfully reset" in the page text

      When I use password reset email for "PasswordResetUser" to visit the link
      And I choose a new password
      Then I see "That password link does not work" in the title

      # the old password is no longer accepted
      When I log in as "PasswordResetUser" test user
      Then I see "Email and password combination not recognised" in the page text

      # change password back to old one.
      When I visit the login page
      And I log in as "PasswordResetUser" with new password
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

      When I visit "/forgot-password/reset/aaaaaaaaaaaaaaaaaaaa"
      Then I see "Reset your password" in the title
      And I can find "reset-my-password" and it is visible
      And I visit "/user/dashboard" without being logged in

  @PasswordReset
  Scenario: Asking for a reset link validates the address and does not disclose whether it exists
    Given I visit "/forgot-password"
    Then I see "Reset your password" in the title

    When I click "email-me-the-link"
    Then I see "There is a problem" in the page text
    And I see "Enter your email address" in the page text
    And I see "Error" in the title

    When I type "someone@lpa.opg.service.justice.gov.uk" into "email"
    And I type "someone.else@lpa.opg.service.justice.gov.uk" into "email_confirm"
    And I click "email-me-the-link"
    Then I see "Enter matching email addresses" in the page text

    When I type "no.such.account@lpa.opg.service.justice.gov.uk" into "email"
    And I type "no.such.account@lpa.opg.service.justice.gov.uk" into "email_confirm"
    And I click "email-me-the-link"
    Then I see "We've emailed a link" in the page text
    And I see "no.such.account@lpa.opg.service.justice.gov.uk" in the page text

  @PasswordReset
  Scenario: The reset password screen enforces the password rules
    Given I visit "/forgot-password/reset/aaaaaaaaaaaaaaaaaaaa"
    Then I see "Reset your password" in the title
    And I see "at least 12 characters" in the page text

    When I click "reset-my-password"
    Then I see "There is a problem" in the page text
    And I see "Enter your password" in the page text
    And I see "Error" in the title

    When I type "Sh0rtPass" into "password"
    And I type "Sh0rtPass" into "password_confirm"
    And I click "reset-my-password"
    Then I see "Your password must be at least twelve characters long" in the page text

    When I type "passwordwithnodigits" into "password"
    And I type "passwordwithnodigits" into "password_confirm"
    And I click "reset-my-password"
    Then I see "Your password must include at least one digit (0-9)" in the page text
    And I see "Your password must include at least one capital letter (A-Z)" in the page text

    When I type "ValidPassword123" into "password"
    And I type "DifferentPassword123" into "password_confirm"
    And I click "reset-my-password"
    Then I see "Enter matching passwords" in the page text

  @PasswordReset
  Scenario: A reset link that is no longer valid tells the user how to get a new one
    Given I visit "/forgot-password/reset/aaaaaaaaaaaaaaaaaaaa"
    When I type "ValidPassword123" into "password"
    And I type "ValidPassword123" into "password_confirm"
    And I click "reset-my-password"
    Then I see "That password link does not work" in the title
    And I see "already used this link" in the page text

    When I visit link containing "Get a new link"
    Then I am taken to "/forgot-password"

  @PasswordReset
  Scenario: A reset link mangled by an email client still explains itself (LPAL-2289)
    Given I visit "/forgot-password/reset/aaaaaaaaaaaaaaaaaaaa."
    Then I see "That password link does not work" in the title
    And I see "changed the link" in the page text

    When I visit link containing "Get a new link"
    Then I am taken to "/forgot-password"

  @PasswordReset
  Scenario: The reset screen lets me reveal my password instead of confirming it (LPAL-2289)
    Given I visit "/forgot-password/reset/aaaaaaaaaaaaaaaaaaaa"
    Then I can find "password_confirm" and it is visible

    When I type "ValidPassword123" into "password"
    And I reveal the password I have typed
    Then the password I typed is shown as plain text
    And I am not asked to confirm the password

    When I click "reset-my-password"
    Then I see "That password link does not work" in the title
