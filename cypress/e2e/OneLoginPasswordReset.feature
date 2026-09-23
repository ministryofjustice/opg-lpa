@OneLoginPasswordReset
Feature: OneLoginPasswordReset

  Linking an account to GOV.UK One Login clears its password, but the account is still
  findable by its old email address. Before LPAL-2427 the forgotten-password journey issued
  it a reset link anyway, and completing the reset restored password sign-in alongside One
  Login — silently undoing the link.

  These scenarios rely on reset tokens being sha1(email) outside production, which is how the
  suite follows an emailed link without reading email: visiting the address the link would
  have used tells us whether a token was issued at all.

  Scenario: An account linked to One Login is refused a password reset link
    When I ask for a password reset link for the "already linked" account
    Then I see "We've emailed a link" in the page text

    When I use the password reset link for the "already linked" account
    And I choose a new password
    Then I see "That password link does not work" in the title

  Scenario: An account created through One Login is refused, found by its One Login email
    When I ask for a password reset link for the "created through One Login" account
    Then I see "We've emailed a link" in the page text

    When I use the password reset link for the "created through One Login" account
    And I choose a new password
    Then I see "That password link does not work" in the title

  Scenario: An ordinary account still gets a working reset link
    Given I sign up "OneLoginResetControlUser" test user with password "Pass12345678"
    When I use activation email for "OneLoginResetControlUser" to visit the link
    Then I see "Account activated" in the title

    When I visit "/forgot-password"
    And I populate email fields with "OneLoginResetControlUser" user address
    Then I see "We've emailed a link" in the page text

    When I use password reset email for "OneLoginResetControlUser" to visit the link
    And I choose a new password
    Then I see "Password successfully reset" in the page text

  Scenario: Repeated password sign-in attempts never lock a One Login account
    When I attempt to sign in 6 times with an incorrect password as the "already linked" account
    Then I see "Email and password combination not recognised" in the page text
    And I do not see "This user account has been locked" in the page text
