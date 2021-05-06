@Admin
Feature: Admin

  I want to be able to visit the admin page and log in

  @focus
  Scenario: Log in to admin and search for a user
    Given I log in to admin
    When I click "find-users-link"
    Then I am taken to the find users page

    When I type "madeup" into "query-input"
    And I click "submit-button"
    Then there are "two" ".user-find-result" elements on the page
    And the first activation date is "21st Jan 2020 at 3:15:53 pm"
    And the second activation date is "21st Jan 2020 at 3:15:53 pm"

    When I click element marked "barmadeup"
    Then the email address input contains "barmadeup@digital.justice.gov.uk"
    And the first activation date is "21st Jan 2020 at 3:15:53 pm"
