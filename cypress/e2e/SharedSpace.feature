@SharedSpace
Feature: Shared Space

  Scenario: Can create a shared space from user dashboard
    Given I create a new user with 5 LPAs
    When I log in as the newly created fixture user
    Then I should be on "/user/dashboard"
    And there are "five" 'LPA' elements on the page
    When I click element marked "Shared Spaces"
    Then I should be on "/shared-space/about"
    When I click element marked "Create new shared space"
    Then I should be on "/shared-space/make"
    When I type "Example Organisation" into "space-name"
    When I click element marked "Create shared space"
    Then I should be on "/shared-space/created"
    When I click element marked "Continue"
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
    And "Member 1" should be an "admin"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I uncheck "permissions" checkbox
    And I click element marked "Save"
    Then I should be on "/shared-space/manage"
    And "Member 1" should be a "member"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I check "permissions" checkbox
    And I click element marked "Save"
    Then I should be on "/shared-space/manage"
    And "Member 1" should be a "admin"
