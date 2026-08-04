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
