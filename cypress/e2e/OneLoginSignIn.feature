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
