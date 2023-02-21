@Admin
Feature: AdminUserAccount

  # next 4 scenarios are linked sequentially to test account deletion. The scenarios are separate because they're on different urls
  Scenario: Sign up user on user-facing site
    When I sign up with email "SignupAndDeleteUser@digital.justice.gov.uk" and password "Pass1234"
    Then I see "Please check your email" in the title
    And I do not see "There is a problem" in the page text
    And I see "Please check your email" in the page text

  # this scenario has to be seperate as it requires visiting a different url, but relies on the previous scenario running
  Scenario: Search for new user in admin
    Given I visit the admin sign-in page
    And I log in to admin
    When I click "user-search-link"
    And I type "signupanddeleteuser@digital.justice.gov.uk" into "email-address-input" working around cypress bug
    And I click "submit-button"
    Then there is "a single" ".user-search-result" element on the page
    And the first user account status is "Not activated"
