@RequiresOneLogin
Feature: Onboarding into Make after signing in with GOV.UK One Login

  When I sign in to Make with GOV.UK One Login for the first time
  I am asked a few questions to set up my Make account
  So that the service fits how I make LPAs

  Background:
    Given I visit "/login"

  Scenario: A new One Login user is first asked which best describes them
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    Then I am asked which best describes me
    And I see the One Login header but not the Make navigation

  Scenario: Continuing without an answer asks the user to choose one
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I continue without saying which best describes me
    Then I am told to select which best describes me

  Scenario Outline: Either answer continues the onboarding
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "<description>"
    Then I am asked whether I have an existing Make account
    And I see the One Login header but not the Make navigation

    Examples:
      | description                     |
      | myself, family or friends       |
      | other people as part of my work |

  Scenario: Coming back to the question shows the answer already given
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "other people as part of my work"
    And I go back to which best describes me
    Then my answer that I make LPAs for "other people as part of my work" is still selected

  Scenario: The question cannot be skipped
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I visit "/keep-your-lpas"
    Then I am asked which best describes me

  Scenario: Continuing without saying whether I have an account asks me to choose
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I continue without saying whether I have an existing Make account
    Then I am told to select whether I have an existing Make account

  Scenario: A user with an existing Make account is asked whether to keep their LPAs
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I say "Yes, I already have an account"
    Then I am asked whether I want to keep my existing LPAs
    And I see the One Login header but not the Make navigation

  Scenario: Continuing without saying whether to keep my LPAs asks me to choose
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I say "Yes, I already have an account"
    And I continue without saying whether I want to keep my existing LPAs
    Then I am told to select whether I want to keep my existing LPAs

  Scenario: Keeping my LPAs by signing in to my existing account takes me to it
    Given I create a new user with 1 LPA
    When I sign in through mock One Login to keep my existing Make account
    Then I am asked to sign in to my existing Make account
    And I see the One Login header but not the Make navigation
    When I link the newly created fixture users Make account
    Then I am taken to "/user/dashboard?account=linked"
    And I see a success notification with content "The account information you chose to keep is now available in this account"

  Scenario: Signing in to my existing account without my details asks for them
    When I sign in through mock One Login to keep my existing Make account
    And I try to sign in to my existing Make account as "" with no password
    Then I see the error "Enter your email address"
    And I see the error "Enter your password"

  Scenario: Signing in to my existing account with an email address in the wrong format asks me to correct it
    When I sign in through mock One Login to keep my existing Make account
    And I try to sign in to my existing Make account as "not-an-email" with no password
    Then I see the error "Enter an email address in the correct format, like name@example.com"

  Scenario: A wrong password means the account cannot be linked, and I can try again
    Given I create a new user with 1 LPA
    When I sign in through mock One Login to keep my existing Make account
    And I attempt to sign in to my existing Make account with the newly created fixture users incorrect password
    Then I should be on "/cannot-link-account"
    And I am advised my account could not be linked
    When I choose to try again
    Then I am asked whether I want to keep my existing LPAs
    When I say "Yes" to keeping my existing LPAs
    And I link the newly created fixture users Make account
    And If I am on dashboard I click to create lpa
    Then I am taken to the lpa type page

  Scenario: An account already linked to another GOV.UK One Login cannot be linked
    When I sign in through mock One Login to keep my existing Make account
    And I attempt to link a Make account already linked to another One Login
    Then I should be on "/cannot-link-account"
    And I am advised my account could not be linked

  Scenario: A locked Make account cannot be linked, even with the right password
    Given I create a new user with 1 LPA
    And the newly created fixture user has locked their account
    And I visit "/login"
    When I sign in through mock One Login to keep my existing Make account
    And I link the newly created fixture users Make account
    Then I should be on "/cannot-link-account"
    And I am advised my account could not be linked

  Scenario: A Make account that was never activated cannot be linked
    Given I sign up "NotActivatedLinkUser" test user with password "Pass12345678"
    And I visit "/login"
    When I sign in through mock One Login to keep my existing Make account
    And I link the "NotActivatedLinkUser" test users Make account
    Then I should be on "/cannot-link-account"
    And I am advised my account could not be linked

  Scenario: A deleted Make account cannot be linked
    Given I create a new user with 1 LPA
    And the newly created fixture users account has been deleted
    When I sign in through mock One Login to keep my existing Make account
    And I link the newly created fixture users Make account
    Then I should be on "/cannot-link-account"
    And I am advised my account could not be linked

  Scenario: After failing to link an account I can continue to make a new account
    Given Ordnance Survey postcode lookup responses are stubbed out for good postcode B1 1TF
    When I sign in through mock One Login to keep my existing Make account
    And I attempt to link a Make account already linked to another One Login
    Then I should be on "/cannot-link-account"
    And I am advised my account could not be linked
    When I choose to create a new account
    Then I am asked for my details
    When I click element marked "Search for UK postcode"
    And I type "B1 1TF" into "postcode-lookup" working around cypress bug
    And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
    And I click element marked "Find UK address"
    And I select option "THE OFFICE OF THE PUBLIC GUARDIAN, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM" of "address-search-result"
    Then I see "address-postcode" prepopulated with "B1 1TF"
    When I enter my name and date of birth and save
    Then I am taken to "/user/dashboard?account=created"
    And I see a success notification with content "You’ve created your Make a lasting power of attorney account"

  Scenario: I can reset a forgotten password, or go back, from signing in to my existing account
    When I sign in through mock One Login to keep my existing Make account
    And I go back
    Then I am asked whether I want to keep my existing LPAs
    And my answer "Yes" to keeping my existing LPAs is still selected
    When I say "Yes" to keeping my existing LPAs
    And I choose to reset my Make account password
    Then I am asked for the email address of my existing Make account
    When I go back
    Then I am asked to sign in to my existing Make account

  Scenario: Resetting my password without my email address asks for it
    When I sign in through mock One Login to keep my existing Make account
    And I choose to reset my Make account password
    And I ask for a password reset link for "" confirmed as ""
    Then I see the error "Enter your email address"
    And I see the error "Confirm your email address"

  Scenario: Resetting my password with an email address in the wrong format asks me to correct it
    When I sign in through mock One Login to keep my existing Make account
    And I choose to reset my Make account password
    And I ask for a password reset link for "not-an-email" confirmed as "not-an-email"
    Then I see the error "Enter an email address in the correct format, like name@example.com"

  Scenario: Resetting my password with email addresses that do not match asks me to correct them
    When I sign in through mock One Login to keep my existing Make account
    And I choose to reset my Make account password
    And I ask for a password reset link for "me@example.com" confirmed as "someone-else@example.com"
    Then I see the error "Enter matching email addresses"

  Scenario: I can ask for a link to reset my forgotten password
    When I sign in through mock One Login to keep my existing Make account
    And I choose to reset my Make account password
    And I ask for a password reset link for "me@example.com" confirmed as "me@example.com"
    Then I see "We've emailed a link" in the page text

  Scenario: Resetting a forgotten password lets me link my existing account with the new one
    Given I create a new user with 1 LPA
    When I sign in through mock One Login to keep my existing Make account
    And I choose to reset my Make account password
    And I ask for a password reset link for the newly created fixture user
    Then I see "We've emailed a link" in the page text
    When I use the password reset link for the newly created fixture user
    And I choose a new password
    Then I am returned to the appropriate page shown after a password reset
    When I visit "/login"
    And I sign in through mock One Login to keep my existing Make account
    And I link the newly created fixture users Make account with my new password
    Then I am taken to "/user/dashboard?account=linked"

  Scenario: Not keeping my LPAs starts a new account, and going back keeps my answer
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I say "Yes, I already have an account"
    And I say "No" to keeping my existing LPAs
    Then I am asked for my details
    When I go back
    Then I am asked whether I want to keep my existing LPAs
    And my answer "No" to keeping my existing LPAs is still selected
    When I go back
    Then I am asked whether I have an existing Make account

  Scenario: A user new to Make is not asked about keeping LPAs or signing in to an account
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I say "No, I am new to this service"
    And I visit "/keep-your-lpas"
    Then I am asked whether I have an existing Make account
    When I visit "/link-account"
    Then I am asked whether I have an existing Make account

  Scenario: A user new to Make enters their details and their account is created
    Given Ordnance Survey postcode lookup responses are stubbed out for good postcode B1 1TF
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "other people as part of my work"
    And I say "No, I am new to this service"
    Then I am asked for my details
    And I see the One Login header but not the Make navigation
    And I can look up my address while onboarding
    When I click element marked "Search for UK postcode"
    And I type "B1 1TF" into "postcode-lookup" working around cypress bug
    And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
    And I click element marked "Find UK address"
    And I select option "THE OFFICE OF THE PUBLIC GUARDIAN, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM" of "address-search-result"
    Then I see "address-postcode" prepopulated with "B1 1TF"
    When I enter my name and date of birth and save
    Then I am taken to "/user/dashboard?account=created"
    And I see a success notification with content "You’ve created your Make a lasting power of attorney account"
    Then my new Make account has my details
    And I see "address-postcode" prepopulated with "B1 1TF"

  Scenario: Saving my details without my name asks me for it and creates no account
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I say "No, I am new to this service"
    And I save my details without my name
    Then I am told to enter my name
    And I see the One Login header but not the Make navigation

  Scenario: Going back from my details keeps my answer about an existing account
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I say "No, I am new to this service"
    And I go back
    Then I am asked whether I have an existing Make account
    And my answer "No, I am new to this service" is still selected

  Scenario: My details are only asked for once I have said I am new to Make
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I visit "/your-details"
    Then I am asked whether I have an existing Make account

  Scenario: Going back keeps the answers already given
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "other people as part of my work"
    And I say "Yes, I already have an account"
    And I visit "/existing-account"
    Then my answer "Yes, I already have an account" is still selected
    When I go back
    Then I am asked which best describes me
    And my answer that I make LPAs for "other people as part of my work" is still selected

  Scenario: Whether I have an account cannot be asked before which best describes me
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I visit "/existing-account"
    Then I am asked which best describes me

  Scenario: The One Login header stays on other pages while the user is still onboarding
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I visit "/guide"
    Then I can find "one-login-header"
    And I cannot find "banner"

  Scenario: Signing out while onboarding also signs the user out of One Login
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I sign out and am signed out of One Login
    Then I am taken to the post logout url

  Scenario: Reaching the question without a One Login session returns to sign in
    Then I visit "/which-best-describes-you" without being logged in
