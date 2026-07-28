@RequiresOneLogin
Feature: One Login Sign In
  As a Make User
  When I want to access the service
  And One Login is the mechanism to do so
  Then I am directed to One Login to access or create an account

  Background:
    Given I visit "/login"

  Scenario: Login page shows the GOV.UK One Login button
    Then I can find "onelogin-signin-button" and it is visible

  Scenario: Clicking the One Login button redirects to the mock One Login service
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page

  Scenario: Completing sign-in through One Login hands off to the link-or-create-account page
    Then I click "onelogin-signin-button"
    And I am on the mock One Login page
    And I continue through mock One Login
    And I am taken to "/link-or-create-account"

  Scenario: The One Login callback fails gracefully when the provider returns an error
    Then the One Login callback shows the problem page for "?error=access_denied"

  Scenario: The One Login callback fails gracefully for an incomplete request
    Then the One Login callback shows the problem page for ""
