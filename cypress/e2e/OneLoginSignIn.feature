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

  Scenario: Continuing from the login page goes straight to One Login
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page

  Scenario: Signing in through One Login as a new user starts onboarding
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I am asked which best describes me

  Scenario: Signing out of Make also signs the user out of One Login
    Given I sign in through mock One Login with a new Make account
    When I sign out and am signed out of One Login
    Then I am taken to the post logout url

  Scenario: Timing out of Make also signs the user out of One Login, even after a background request
    Given I ignore application exceptions
    And I sign in through mock One Login with a new Make account
    When I hack the session to have 0 seconds remaining
    And I wait for 3 seconds
    And a background request to "/user/dashboard/statuses/1" is sent to the timeout page, not One Login
    And I return to "/user/about-you" after timing out and am signed out of One Login
    Then I see "We’ve signed you out" in the page text

  Scenario: A password user who signs out is not sent to One Login
    Given I log in as appropriate test user
    Then I sign out without going through One Login

  Scenario: A password user who times out is not sent to One Login
    Given I ignore application exceptions
    And I log in as appropriate test user
    When I hack the session to have 0 seconds remaining
    And I wait for 3 seconds
    And I return to "/user/about-you" after timing out without going through One Login
    Then I see "We’ve signed you out" in the page text

  Scenario: Callback with errors show the problem page
    When One Login returns a "access_denied" error
    Then I can return to sign in
    When One Login returns a "unauthorized_client" error
    Then I can contact One Login support
    When One Login returns a "unexpected" error
    Then I can return to sign in

  Scenario: Signed-out pages show the GOV.UK header, not the One Login header
    Then I can find "banner"
    And I cannot find "one-login-header"

  Scenario: Users signed in through One Login get the One Login header, which carries the sign-out link
    Given I sign in through mock One Login with a new Make account
    Then I can find "one-login-header" and it is visible
    And I cannot find "banner"
    And the "one-login-account-link" link goes to "https://home.account.gov.uk"
    And the "sign-out" link goes to "/logout"
    And the service navigation has no sign-out link

  Scenario: A password user keeps the GOV.UK header and the service navigation's sign-out link
    Given I log in as appropriate test user
    Then I can find "banner"
    And I cannot find "one-login-header"
    And the service navigation has a sign-out link

  Scenario: A returning user whose account is linked goes straight to their LPAs
    Given I create a new user with 1 LPA
    And the newly created fixture user has linked their Make account to One Login
    And I sign out and am signed out of One Login
    When I log in through Onelogin as the newly created fixture user
    Then I am taken to "/user/dashboard"
    And I am signed in to Make

  Scenario: A returning user who follows a link while signed out is taken back to it after signing in
    Given I create a new user with 1 LPA
    And the newly created fixture user has linked their Make account to One Login
    And I sign out and am signed out of One Login
    When I visit "/user/about-you" without being logged in
    And I click "onelogin-signin-button"
    And I continue through mock One Login as the newly created fixture user
    Then I am taken to "/user/about-you"

  Scenario: A new user who follows a link while signed out is taken back to it after onboarding
    When I visit "/user/about-you" without being logged in
    And I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I create a new Make account
    Then I am taken to "/user/about-you"

  Scenario: The back-channel logout endpoint answers One Login with a 200 for a token it cannot accept
    Then a back-channel logout request with "no logout token" is answered with a 200
    And a back-channel logout request with "an invalid logout token" is answered with a 200

  Scenario: The JWKS endpoint publishes the key One Login uses to check our signed requests
    Then the JWKS endpoint publishes our signing key
