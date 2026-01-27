@Admin
Feature: Admin

  I want to be able to visit the admin page and carry out admin functions

  Background:
    Given I log in to admin

  Scenario: Find users, search for deleted user
    When I click "find-users-link"
    Then I am taken to the find users page

    # search for the users seeded into the database
    When I type "FindUser" into "query-input" working around cypress bug
    And I click "submit-button"
    Then there are "ten" '[data-cy="user-summary-card"]' elements on the page
    And the first user email address is "FindUser_Paging42MzQ5OTU10@uat.justice.gov.uk"

    # next
    When I click element marked "Next"
    Then there are "ten" '[data-cy="user-summary-card"]' elements on the page
    And the first user email address is "FindUser_Paging42MzQ5OTU20@uat.justice.gov.uk"

    # previous
    When I click element marked "Previous"
    Then there are "ten" '[data-cy="user-summary-card"]' elements on the page
    And the first user email address is "FindUser_Paging42MzQ5OTU10@uat.justice.gov.uk"

    When I click element marked "FindUser_Paging42MzQ5OTU10@uat.justice.gov.uk"
    Then the email address input contains "FindUser_Paging42MzQ5OTU10@uat.justice.gov.uk"

    # LPAL-1164: case-insensitive search on find users page
    When I click "find-users-link"
    And I type "finduser" into "query-input" working around cypress bug
    And I click "submit-button"
    Then there are "ten" '[data-cy="user-summary-card"]' elements on the page

    # search for deleted user elliot@townx.org
    When I click "user-search-link"
    And I type "elliot@townx.org" into "email-address-input" working around cypress bug
    And I click "submit-button"
    Then deleted user is displayed with deletion date of "5th May 2021 at 12:21:20 pm"

  Scenario: Set a system message
    Given I visit the admin sign-in page
    And I click "system-message-link"
    Then I am taken to the system message page
    # set message to display on front end
    When I type "Your pizza is burning" into "message"
    And I click "submit-button"
    Then I see "System message set" in the page text

  # due to home page being on different url from admin, this scenario has to be seperate, but relies on previous scenario having run
  Scenario: System message should now be set on user-facing site.
    When I visit "/home"
    Then I see "Your pizza is burning" in the page text
    When I visit "/login"
    Then I see "Your pizza is burning" in the page text
    When I visit "/signup"
    Then I see "Your pizza is burning" in the page text

  Scenario: Remove system message
    Given I visit the admin sign-in page
    And I click "system-message-link"
    # remove message to display on front end
    When I clear the value in "message"
    And I click "submit-button"
    Then I see "System message removed" in the page text

  # due to home page being on different url from admin, this scenario has to be seperate, but relies on previous scenario having run
  Scenario: System message should now be removed on user-facing site.
    When I visit "/home"
    Then I do not see "Your pizza is burning" in the page text
    And the information icon for the system message should not be on the page
    When I visit "/login"
    Then I do not see "Your pizza is burning" in the page text
    And the information icon for the system message should not be on the page
    When I visit "/signup"
    Then I do not see "Your pizza is burning" in the page text
    And the information icon for the system message should not be on the page

  Scenario: Set and remove a system message consecutively i:e having previously set and removed it, we do this again to ensure it still works
    Given I visit the admin sign-in page
    And I click "system-message-link"
    # set message to display on front end
    When I type "Your pizza is burning" into "message"
    And I click "submit-button"
    Then I see "System message set" in the page text

  # due to home page being on different url from admin, this scenario has to be seperate, but relies on previous scenario having run
  Scenario: System message should now be set on user-facing site.
    When I visit "/home"
    Then I see "Your pizza is burning" in the page text
    When I visit "/login"
    Then I see "Your pizza is burning" in the page text
    When I visit "/signup"
    Then I see "Your pizza is burning" in the page text

  Scenario: Remove second system message
    Given I visit the admin sign-in page
    And I click "system-message-link"
    # remove message to display on front end
    When I clear the value in "message"
    And I click "submit-button"
    Then I see "System message removed" in the page text

  # due to home page being on different url from admin, this scenario has to be seperate, but relies on previous scenario having run
  Scenario: System message should now be removed on user-facing site.
    When I visit "/home"
    Then I do not see "Your pizza is burning" in the page text
    When I visit "/login"
    Then I do not see "Your pizza is burning" in the page text
    When I visit "/signup"
    Then I do not see "Your pizza is burning" in the page text

  Scenario: Try to set empty message on system message
    Given I visit the admin sign-in page
    And I click "system-message-link"
    Then I am taken to the system message page

    # set message to display on front end
    When I type " " into "message"
    And I click "submit-button"
    And I see "No system message has been set" in the page text

  Scenario: View feedback sent to the service
    Given I visit the admin sign-in page
    And I click "feedback-link"
    Then I am taken to the feedback page

    # date fields for feedback range
    Then I force fill out "#day-start-date" element with "11"
    And I force fill out "#month-start-date" element with "05"
    And I force fill out "#year-start-date" element with "2025"

    Then I force fill out "#day-end-date" element with "12"
    And I force fill out "#month-end-date" element with "05"
    And I force fill out "#year-end-date" element with "2025"

    # we have expected feedback displayed
    Then I click "submit-button"
    And I see in the page text
        | Details                 |
        | test-no-email-no-number |
        | test-no-num             |
        | test                    |

    # LPAL-1053: check feedback export to CSV works correctly
    And I can export feedback and download it as a CSV file

  # LPAL-1049: long feedback wraps correctly and is visible
  Scenario: View feedback with long details
    Given I visit the admin sign-in page
    And I click "feedback-link"
    Then I am taken to the feedback page

    # date fields for feedback range
    When I force fill out "#day-start-date" element with "28"
    And I force fill out "#month-start-date" element with "11"
    And I force fill out "#year-start-date" element with "2024"
    And I force fill out "#day-end-date" element with "28"
    And I force fill out "#month-end-date" element with "11"
    And I force fill out "#year-end-date" element with "2024"
    And I click "submit-button"

    Then very long feedback details from user "longwindeduser@test.com" displays correctly in the page

  # LPAL-1088: user-supplied data is escaped appropriately
  Scenario: User-supplied data is escaped correctly in the feedback search page
    Given I visit the admin sign-in page
    And I click "feedback-link"
    Then I am taken to the feedback page

    # date fields for feedback range
    When I force fill out "#day-start-date" element with "02"
    And I force fill out "#month-start-date" element with "12"
    And I force fill out "#year-start-date" element with "2024"
    And I force fill out "#day-end-date" element with "02"
    And I force fill out "#month-end-date" element with "12"
    And I force fill out "#year-end-date" element with "2024"
    And I click "submit-button"

    Then I see "<script>alert(\"hello email\");</script>test@test.com" in the page text
    And I see "<script>alert(\"hello phone\");</script>01234567891" in the page text
    And I see "<script>alert(\"hello details\");</script>\"great service\" test" in the page text
    And I see "<script>alert(\"hello fromPage\");</script>/feedback-thanks" in the page text
    And I see "<script>alert(\"hello agent\");</script>" in the page text
