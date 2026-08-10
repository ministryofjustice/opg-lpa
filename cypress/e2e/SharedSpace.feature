@SharedSpace
Feature: Shared Space

  Scenario: Can create a shared space from user dashboard
    Given I create a new user with 5 LPAs
    When I log in as the newly created fixture user
    Then I should be on "/user/dashboard"
    And there are "five" 'LPA' elements on the page
    When I click element marked "Make a shared space"
    Then I should be on "/shared-space/about"
    When I click element marked "Continue"
    Then I should be on "/shared-space/make"
    When I type "Example Organisation" into "space-name"
    When I click element marked "Create shared space"
    Then I should be on "/shared-space/dashboard"
    And there are "five" 'LPA' elements on the page
    When I click element marked "Manage your Shared Space"
    Then I should be on "/shared-space/manage"
    And I can see myself

  Scenario: Can manage admin permissions of other members
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is an "admin"
    And I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Manage your Shared Space"
    Then I should be on "/shared-space/manage"
    And "Member 1" permissions should be set to "admin"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I uncheck checkbox labelled "Admin"
    And I click element marked "Save"
    Then I should be on "/shared-space/manage"
    And "Member 1" permissions should be set to "member"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I check checkbox labelled "Admin"
    And I click element marked "Save"
    Then I should be on "/shared-space/manage"
    And "Member 1" permissions should be set to "admin"

  Scenario: Can invite a member to a shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And I log in as the newly created fixture user
    When I click element marked "Manage your Shared Space"
    Then I should be on "/shared-space/manage"
    When I click element marked "Invite member"
    Then I should be on "/shared-space/invite"
    When I type "John" into field labelled "First names"
    And I type "Smith" into field labelled "Last name"
    And I type "john.smith@example.com" into field labelled "Email"
    Then I submit the form
    Then I should be on "/shared-space/manage"
    And I see a success notification with content "Invite sent"

  Scenario: Can revoke a members invite
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And I log in as the newly created fixture user
    When I click element marked "Manage your Shared Space"
    Then I should be on "/shared-space/manage"
    When I click element marked "Invite member"
    Then I should be on "/shared-space/invite"
    When I type "John" into field labelled "First names"
    And I type "Smith" into field labelled "Last name"
    And I type "john.smith@example.com" into field labelled "Email"
    Then I submit the form
    Then I should be on "/shared-space/manage"
    And I see a success notification with content "Invite sent"
    When I click element marked "Revoke invite"
    Then I should be on "/shared-space/revoke-invite/"
    Then I submit the form
    Then I should be on "/shared-space/manage"
    And I see a success notification with content "Invite revoked"

  Scenario: Can suspend a member from a shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is an "admin"
    And I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Manage your Shared Space"
    Then I should be on "/shared-space/manage"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I select radio labelled "Suspend access to this shared space"
    And I click element marked "Save"
    Then I should be on "/shared-space/manage"
    And "Member 1" status should be "suspended"
    When I try to log in as the member added to the shared space
    Then I should not be logged in and I see a suspended account error
    When I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Manage your Shared Space"
    Then I should be on "/shared-space/manage"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I select radio labelled "Allow access to this shared space"
    And I click element marked "Save"
    Then I should be on "/shared-space/manage"
    And "Member 1" status should be "active"
