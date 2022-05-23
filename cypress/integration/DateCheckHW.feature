Feature: Check signature dates for an HW LPA

  Background:
    Given I ignore application exceptions
    And I log in as seeded user

  Scenario: View the Check Signature Dates tool
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 68582508781
    Then I am taken to "/lpa/68582508781/date-check"
    And I see "Check signature dates" in the title
    And there are "four" ".date-check-person" elements on the page
    And I can see fields for the HW donor, certificate provider, attorney, applicant
