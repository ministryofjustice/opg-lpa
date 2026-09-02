@SharedSpace
Feature: Shared Space

  Scenario: Can create a shared space from user dashboard
    Given I create a new user with 5 LPAs
    When I log in as the newly created fixture user
    Then I should be on "/user/dashboard"
    And there are "five" 'LPA' elements on the page
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    And I see "Shared space" in the title
    When I click element marked "Create shared space"
    Then I should be on "/shared-space/make"
    When I type "Example Organisation" into "space-name"
    When I click element marked "Save"
    Then I should be on "/shared-space/created"
    And I see a success notification with content "Example Organisation has been successfully created"
    When I click element marked "Continue"
    Then I should be on "/shared-space/dashboard"
    And there are "five" 'LPA' elements on the page
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    And I see "Manage your Shared Space" in the title
    And I can see myself

  Scenario: Can manage admin permissions of other members
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is an "admin"
    And I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    And "Member 1" permissions should be set to "admin"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I uncheck checkbox labelled "Admin"
    And I click element marked "Save"
    Then I should be on "/shared-space"
    And "Member 1" permissions should be set to "member"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I check checkbox labelled "Admin"
    And I click element marked "Save"
    Then I should be on "/shared-space"
    And "Member 1" permissions should be set to "admin"

  Scenario: Can invite a member to a shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And I log in as the newly created fixture user
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Invite member"
    Then I should be on "/shared-space/invite"
    When I type "John" into field labelled "First names"
    And I type "Smith" into field labelled "Last name"
    And I type "john.smith@example.com" into field labelled "Email"
    Then I submit the form
    Then I should be on "/shared-space"
    And I see a success notification with content "Invite sent"

  Scenario: Can revoke a members invite
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And I log in as the newly created fixture user
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Invite member"
    Then I should be on "/shared-space/invite"
    When I type "John" into field labelled "First names"
    And I type "Smith" into field labelled "Last name"
    And I type "john.smith@example.com" into field labelled "Email"
    Then I submit the form
    Then I should be on "/shared-space"
    And I see a success notification with content "Invite sent"
    When I click element marked "Revoke invite"
    Then I should be on "/shared-space/revoke-invite/"
    Then I submit the form
    Then I should be on "/shared-space"
    And I see a success notification with content "Invite revoked"

  Scenario: Can suspend a member from a shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is an "admin"

    When I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I select radio labelled "Suspend access to this shared space"
    And I click element marked "Save"
    Then I should be on "/shared-space"
    And "Member 1" status should be "suspended"

    When I try to log in as the member added to the shared space
    Then I should be on "/shared-space/dashboard"
    And I see a notification with title "Access suspended"

    When I try to log in as "fixtureUser"
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I select radio labelled "Allow access to this shared space"
    And I click element marked "Save"
    Then I should be on "/shared-space"
    And "Member 1" status should be "active"

  Scenario: Can join a shared space
    Given I have been invited to a shared space called "Example Organisation" with 1 LPA
    When I log in as the newly created fixture user
    Then I should be on "/user/dashboard"
    And I click element marked "Shared space"
    And I should be on "/shared-space"
    And I see "Shared space" in the title
    And I click link "Join shared space"
    And I should be on "/shared-space/join"
    And I type "Example Organisation" into field labelled "Shared space name"
    And I type the access code into field labelled "Your shared space access code"
    And I click element marked "Continue"
    And I should be on "/shared-space/dashboard"
    And I see a success notification with content "Shared Space joined"
    And I click element marked "Shared space"
    And I cannot see any invites

  Scenario: Can delete a member from a shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is an "admin" with 1 LPA
    And I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    And there are "two" "LPA" elements on the page
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I click link "Delete user"
    Then I should be on page matching "/shared-space/members/.+/delete"
    Then I click button "Delete"
    Then I should be on "/shared-space"
    And I see a success notification with content "Member deleted"
    When I click link "Shared LPAs"
    Then I should be on "/shared-space/dashboard"
    And there are "two" "LPA" elements on the page
    When I try to log in as the member added to the shared space
    Then I should be on "/login"
    Then I should not be logged in

  Scenario: Can import LPAs from an existing user
    Given I create a new user stored as "userToImport" with 5 LPAs
    And I create a new user with 5 LPAs that belongs to a shared space called "Example Organisation"
    When I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click link "Import LPAs from existing account"
    And I enter the login details of "userToImport"
    And I click button "Import"
    And I see a success notification with content "Import success"
    When I click link "Shared LPAs"
    And there are "ten" 'LPA' elements on the page
    When I try to log in as "userToImport"
    Then I should be on "/login"
    Then I should not be logged in

  Scenario: Cannot import LPAs from a user who is in a shared space
    Given I create a new user stored as "userToImport" with 5 LPAs that belongs to a shared space called "Another Space"
    And I create a new user with 5 LPAs that belongs to a shared space called "Example Organisation"
    When I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click link "Import LPAs from existing account"
    And I enter the login details of "userToImport"
    And I click button "Import"
    Then I should be on "/shared-space/import-failed"
    And I click link "Continue"
    Then I should be on "/shared-space"

  Scenario: Can ask for password reset when importing
    Given I create a new user stored as "userToImport" with 5 LPAs
    And I create a new user with 5 LPAs that belongs to a shared space called "Example Organisation"
    When I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click link "Import LPAs from existing account"
    When I click link "Forgotten your password?"
    Then I should be on "/shared-space/forgot-password"
    Then I enter the email of "userToImport"
    Then I submit the form
    Then I should be on "/shared-space/forgot-password"
    Then I see "Thank you" in the page text
