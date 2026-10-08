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

  Scenario: Starting sign in redirects to the One Login authorize endpoint
    Then starting One Login sign in redirects to the authorize endpoint

  @RequiresMockOneLogin
  Scenario: Continuing from the login page goes straight to One Login
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page

  @RequiresMockOneLogin
  Scenario: Signing in through One Login reaches the link-or-create-account page
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"

  @RequiresMockOneLogin
  Scenario: An unlinked user links their existing Make account and reaches the dashboard
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"
    And I choose to link an existing Make account
    And I submit the form
    And I should be on "/link-account"
    And I link the "link" Make account
    And I am taken to the dashboard page

  @RequiresMockOneLogin
  Scenario: An unlinked user chooses to create a new Make account
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"
    And I choose to create a new Make account
    And I submit the form
    And I am signed in with my new Make account
    And I am taken to the your details page for a new user
    And I force fill out
      | name-title| Mr |
      | name-first | Brand |
      | name-last | New |
      | dob-date-day | 1 |
      | dob-date-month | 2 |
      | dob-date-year | 1980 |
      | address-address1 | 123 Test Street |
      | address-postcode | SW1A 1AA |
    And I click "save"
    And If I am on dashboard I click to create lpa
    Then I am taken to the lpa type page

  @RequiresMockOneLogin
  Scenario: An unlinked user entering incorrect credentials is advised and can retry
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"
    And I choose to link an existing Make account
    And I submit the form
    And I should be on "/link-account"
    And I attempt to link the "retry" Make account with an incorrect password
    And I should be on "/link-account"
    And I am advised my Make account credentials were not recognised
    And I link the "retry" Make account
    And If I am on dashboard I click to create lpa
    And I am taken to the lpa type page

  @RequiresMockOneLogin
  Scenario: An unlinked user who has forgotten their password can go to reset it from the link page
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I should be on "/link-or-create-account"
    And I choose to link an existing Make account
    And I submit the form
    And I should be on "/link-account"
    And I attempt to link the "forgot" Make account with an incorrect password
    And I am advised my Make account credentials were not recognised
    And I choose to reset my Make account password
    And I should be on "/forgot-password"

  @RequiresMockOneLogin
  Scenario: Being told a Make account cannot be linked returns the user to the link-or-create question
    Then I click "onelogin-signin-button"
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

  @RequiresMockOneLogin
  Scenario: Signing out of Make also signs the user out of One Login
    Given I sign in through mock One Login with a new Make account
    When I sign out and am signed out of One Login
    Then I am taken to the post logout url

  @RequiresMockOneLogin
  Scenario: Timing out of Make also signs the user out of One Login, even after a background request
    Given I ignore application exceptions
    And I sign in through mock One Login with a new Make account
    When I hack the session to have 0 seconds remaining
    And I wait for 3 seconds
    And a background request to "/user/dashboard/statuses/1" is sent to the timeout page, not One Login
    And I return to "/user/about-you" after timing out and am signed out of One Login
    Then I see "We’ve signed you out" in the page text

  @RequiresMockOneLogin
  Scenario: A password user who signs out is not sent to One Login
    Given I log in as appropriate test user
    Then I sign out without going through One Login

  @RequiresMockOneLogin
  Scenario: A password user who times out is not sent to One Login
    Given I ignore application exceptions
    And I log in as appropriate test user
    When I hack the session to have 0 seconds remaining
    And I wait for 3 seconds
    And I return to "/user/about-you" after timing out without going through One Login
    Then I see "We’ve signed you out" in the page text

  Scenario: Reaching the link-account page directly without a One Login session returns to sign in
    Then I visit "/link-account" without being logged in

  Scenario: The One Login callback fails gracefully when the provider returns an error
    Then the One Login callback shows the problem page for "?error=access_denied"

  Scenario: The One Login callback fails gracefully for an incomplete request
    Then the One Login callback shows the problem page for ""

  Scenario: Signed-out pages show the GOV.UK header, not the One Login header
    Then I can find "banner"
    And I cannot find "one-login-header"

  Scenario: Signed-in users get the One Login header, which carries the sign-out link
    Given I log in as appropriate test user
    Then I can find "one-login-header" and it is visible
    And I cannot find "banner"
    And the "one-login-account-link" link goes to "https://home.account.gov.uk"
    And the "sign-out" link goes to "/logout"
    And the service navigation has no sign-out link
