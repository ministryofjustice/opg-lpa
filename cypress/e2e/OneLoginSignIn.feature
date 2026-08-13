@RequiresOneLogin
Feature: One Login Sign In
  As a Make User
  When I want to access the service
  And One Login is the mechanism to do so
  Then I am directed to One Login to access or create an account

  Background:
    Given I visit "/login"

  Scenario: The login page offers GOV.UK One Login
    Then I can find "onelogin-signin-button" and it is visible

  Scenario: Continuing with One Login leads to the One Login start page
    Then I click "onelogin-signin-button"
    And I am taken to "/login-onelogin"
    And I can find "onelogin-signin-button" and it is visible
  @RequiresMockOneLogin
  Scenario: Signing in through One Login reaches the link-or-create-account page
    Then I click "onelogin-signin-button"
    And I am taken to "/login-onelogin"
    And I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"

  @RequiresMockOneLogin
  Scenario: An unlinked user links their existing Make account and reaches the dashboard
    Then I click "onelogin-signin-button"
    And I am taken to "/login-onelogin"
    And I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"
    And I choose to link an existing Make account
    And I submit the form
    And I should be on "/link-account"
    And I link my seeded Make account
    And I am taken to the dashboard page

  @RequiresMockOneLogin
  Scenario: An unlinked user chooses to create a new Make account
    Then I click "onelogin-signin-button"
    And I am taken to "/login-onelogin"
    And I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"
    And I choose to create a new Make account
    And I submit the form
    And I should be on "/signup"

  @RequiresMockOneLogin
  Scenario: An unlinked user entering incorrect credentials is advised and can retry
    Then I click "onelogin-signin-button"
    And I am taken to "/login-onelogin"
    And I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"
    And I choose to link an existing Make account
    And I submit the form
    And I should be on "/link-account"
    And I attempt to link my Make account with an incorrect password
    And I should be on "/link-account"
    And I am advised my Make account credentials were not recognised
    And I link my seeded Make account
    And I am taken to the dashboard page

  @RequiresMockOneLogin
  Scenario: An unlinked user who has forgotten their password can go to reset it from the link page
    Then I click "onelogin-signin-button"
    And I am taken to "/login-onelogin"
    And I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"
    And I choose to link an existing Make account
    And I submit the form
    And I should be on "/link-account"
    And I attempt to link my Make account with an incorrect password
    And I am advised my Make account credentials were not recognised
    And I choose to reset my Make account password
    And I should be on "/forgot-password"

  @RequiresMockOneLogin
  Scenario: Being told a Make account cannot be linked returns the user to the link-or-create question
    Then I click "onelogin-signin-button"
    And I am taken to "/login-onelogin"
    And I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"
    And I choose to link an existing Make account
    And I submit the form
    And I should be on "/link-account"
    And I attempt to link a Make account already linked to another One Login
    And I should be on "/cannot-link-account"
    And I am advised my account could not be linked
    And I choose to try again
    And I should be on "/link-or-create-account"

  Scenario: Reaching the link-account page directly without a One Login session returns to sign in
    Then I visit "/link-account" without being logged in

  Scenario: The One Login callback fails gracefully when the provider returns an error
    Then the One Login callback shows the problem page for "?error=access_denied"

  Scenario: The One Login callback fails gracefully for an incomplete request
    Then the One Login callback shows the problem page for ""
