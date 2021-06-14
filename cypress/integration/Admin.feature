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

  @focus
  Scenario: Set and remove a system message on user facing site
    Given I visit the admin sign-in page
    And I log in to admin
    And I click "system-message-link"
    Then I am taken to the system message page

    # set message to display on front end
    When I type "Your pizza is burning" into "message"
    And I click "submit-button"
    And I see "System message set" in the page text

    # remove message to display on front end
    Then I clear the value in "message"
    And I click "submit-button"
    And I see "System message removed" in the page text

  Scenario: Set and remove a system message on user facing site consecutively
    Given I visit the admin sign-in page
    And I log in to admin
    And I click "system-message-link"
    Then I am taken to the system message page

    # set message to display on front end
    When I type "Your pizza is burning" into "message"
    And I click "submit-button"
    And I see "System message set" in the page text

    # remove message to display on front end
    Then I clear the value in "message"
    And I click "submit-button"
    And I see "System message removed" in the page text

    # set message to display on front end
    When I type "Your pizza is burning" into "message"
    And I click "submit-button"
    And I see "System message set" in the page text

    # remove message to display on front end
    Then I clear the value in "message"
    And I click "submit-button"
    And I see "System message removed" in the page text

  Scenario: Try to set empty message on system message
    Given I visit the admin sign-in page
    And I log in to admin
    And I click "system-message-link"
    Then I am taken to the system message page

    # set message to display on front end
    When I type " " into "message"
    And I click "submit-button"
    And I see "No system message has been set" in the page text

  @focus
  Scenario: View feedback sent to the service
    Given I visit the admin sign-in page
    And I log in to admin
    And I click "feedback-link"
    Then I am taken to the feedback page

    # date fields for feedback range
    Then I force fill out "#id-day-start-date" element with "11"
    And I force fill out "#id-month-start-date" element with "05"
    And I force fill out "#id-year-start-date" element with "2021"

    Then I force fill out "#id-day-end-date" element with "12"
    And I force fill out "#id-month-end-date" element with "05"
    And I force fill out "#id-year-end-date" element with "2021"

    # we have expected feedback displayed
    Then I click "submit-button"
    And I see in the page text
        | Details                 |
        | test-no-email-no-number |
        | test-no-num             |
        | test                    |
