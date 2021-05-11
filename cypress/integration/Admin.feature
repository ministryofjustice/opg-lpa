@Admin
Feature: Admin

  I want to be able to visit the admin page and log in

  @focus
  Scenario: Log in to admin, find users, search for deleted user
    Given I log in to admin
    When I click "find-users-link"
    Then I am taken to the find users page

    # search for the users seeded into the database
    When I type "FindUser" into "query-input"
    And I click "submit-button"
    Then there are "ten" ".user-find-result" elements on the page
    And the first activation date is "22nd Jan 2020 at 10:11:53 am"
    And the second last login time is "Never logged in"

    When I click element marked "FindUser_Paging42MzQ5OTU10@uat.justice.gov.uk"
    Then the email address input contains "FindUser_Paging42MzQ5OTU10@uat.justice.gov.uk"
    And the first activation date is "22nd Jan 2020 at 10:11:53 am"

    # search for deleted user elliot@townx.org
    When I click "user-search-link"
    And I type "elliot@townx.org" into "email-address-input"
    And I click "submit-button"
    Then deleted user is displayed with deletion date of "5th May 2021 at 12:21:20 pm"
