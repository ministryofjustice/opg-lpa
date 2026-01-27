@Admin
Feature: AdminUserAccount

  Scenario: Delete user from user-facing site
    # Sign up new user on user-facing site
    Given I sign up "SignupAndDeleteUser" test user with password "Pass12345678"
    When I use activation email for "SignupAndDeleteUser" to visit the link
    Then I see "Account activated" in the title

    # Check new user exists on admin site
    When I visit the admin sign-in page
    And I log in to admin
    And I find "SignupAndDeleteUser" on the admin site
    Then there is "a single" '[data-cy="user-summary-card"]' element on the page
    And the first user account status is "Activated"

    # Delete new user on user-facing site
    When I visit "/home"
    And I log in as "SignupAndDeleteUser" test user
    And I visit "/user/about-you"
    And I select "Mr" on "name-title" with data-inited
    And I force fill out
      | name-first| Chris |
      | name-last| Smith |
      | dob-date-day| 1 |
      | dob-date-month| 12 |
      | dob-date-year| 1982 |
      | address-address1| 12 Highway Close |
      | address-postcode| PL45 9JA |
    And I click "save"
    And I visit "/user/delete"
    And I click element marked "Yes, continue deleting my account"
    Then I see "We've deleted your account" in the page text

    # Check new user deleted on admin site
    When I visit the admin sign-in page
    And I find "SignupAndDeleteUser" on the admin site
    Then there is "a single" '[data-cy="user-summary-card"]' element on the page
    And the first user account status is "Deleted"
    And the first deletion reason is "User manually deleted their account"
