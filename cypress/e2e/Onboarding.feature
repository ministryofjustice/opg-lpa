@RequiresOneLogin
Feature: Onboarding into Make after signing in with GOV.UK One Login

  When I sign in to Make with GOV.UK One Login for the first time
  I am asked a few questions to set up my Make account
  So that the service fits how I make LPAs

  Background:
    Given I visit "/login"

  @RequiresMockOneLogin
  Scenario: A new One Login user is first asked which best describes them
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    Then I am asked which best describes me
    And I see the One Login header but not the Make navigation

  @RequiresMockOneLogin
  Scenario: Continuing without an answer asks the user to choose one
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I continue without saying which best describes me
    Then I am told to select which best describes me

  @RequiresMockOneLogin
  Scenario Outline: Either answer continues the onboarding
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "<description>"
    Then I should be on "/link-or-create-account"

    Examples:
      | description                     |
      | myself, family or friends       |
      | other people as part of my work |

  @RequiresMockOneLogin
  Scenario: Coming back to the question shows the answer already given
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "other people as part of my work"
    And I go back to which best describes me
    Then my answer that I make LPAs for "other people as part of my work" is still selected

  @RequiresMockOneLogin
  Scenario: The question cannot be skipped
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I visit "/link-or-create-account"
    Then I am asked which best describes me

  @RequiresMockOneLogin
  Scenario: The One Login header stays on other pages while the user is still onboarding
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I visit "/guide"
    Then I can find "one-login-header"
    And I cannot find "banner"

  @RequiresMockOneLogin
  Scenario: Signing out while onboarding also signs the user out of One Login
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I sign out and am signed out of One Login
    Then I am taken to the post logout url

  Scenario: Reaching the question without a One Login session returns to sign in
    Then I visit "/which-best-describes-you" without being logged in
