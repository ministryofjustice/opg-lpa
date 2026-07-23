@SharedSpace
Feature: Shared Space

#  TODO: Work out mechanism to either tidy up data or quickly create a user (not via UI)
  Scenario: Can create a shared space from user dashboard
    Given I log in with user "seeded_test_user_shared@publicguardian.gov.uk" password "Pass1234"
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
