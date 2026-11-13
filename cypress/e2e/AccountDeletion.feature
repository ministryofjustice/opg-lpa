@RequiresOneLogin @Remaining2
Feature: Deleting an account that signs in with GOV.UK One Login

  Deleting a Make account leaves the user's GOV.UK One Login account alone: they are not signed
  out of One Login, and signing in again starts onboarding as someone new to Make.

  Uses the delete page steps and wording from LPAL-2544.

  Scenario: A lay user deletes their account
    Given I visit "/login"
    And I sign in through mock One Login with a new Make account
    When I visit "/user/about-you"
    And I go to delete my account
    Then I should be on "/user/delete"
    And I see "Deleting your account will permanently remove your LPAs and personal details from this service." in the page text
    And I see "Deleting your Make a lasting power of attorney account will not affect your GOV.UK One Login." in the page text
    When I confirm deleting my account
    Then I should be on "/deleted"
    And I see "Continue to home page" in the page text

  Scenario: The last member of a shared space deletes their account and can start again as a new user
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the newly created fixture user has linked their Make account to One Login
    When I visit "/user/about-you"
    And I go to delete my account
    Then I should be on "/user/delete"
    And I see "Deleting your account will permanently remove your LPAs and personal details from this service." in the page text
    When I confirm deleting my account
    Then I should be on "/deleted"
    When I log in through Onelogin as the newly created fixture user
    Then I am asked which best describes me

  Scenario: A shared space admin with other non-admin members cannot delete their account until they make another member an admin
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is a "member"
    And the newly created fixture user has linked their Make account to One Login
    When I visit "/user/about-you"
    And I go to delete my account
    Then I should be on "/user/delete"
    And I see "The LPAs belong to the shared space Example Organisation and will still be available to its remaining members." in the page text
    When I confirm deleting my account
    Then element "h1" contains "You’ve not been able to delete your account"
    And I see "You are currently the only admin member of Example Organisation." in the page text
    When I click link "Go to shared space members"
    Then I should be on "/shared-space"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I check checkbox labelled "Admin"
    And I click element marked "Save"
    Then "Member 1" permissions should be set to "admin"
    When I visit "/user/about-you"
    And I go to delete my account
    Then I should be on "/user/delete"
    When I confirm deleting my account
    Then I should be on "/deleted"

  Scenario: A shared space member with other admin deletes their account and can start again as a new user
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is a "admin"
    And the newly created fixture user has linked their Make account to One Login
    When I visit "/user/about-you"
    And I go to delete my account
    Then I should be on "/user/delete"
    And I see "The LPAs belong to the shared space Example Organisation and will still be available to its remaining members." in the page text
    When I confirm deleting my account
    Then I should be on "/deleted"
    When I log in through Onelogin as the newly created fixture user
    Then I am asked which best describes me
