Feature: Check signature dates for a PF LPA

  Continuation sheets are referred to as 'sheets'

  Background:
    Given I ignore application exceptions
    And I log in as seeded user

  Scenario: View the Check Signature Dates tool
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 91155453023
    Then I am taken to "/lpa/91155453023/date-check"
    And I see "Check signature dates" in the title
    And there are "four" ".date-check-person" elements on the page
    And I can see fields for the PF donor, certificate provider, attorney, applicant

  Scenario: An LPA with trust corporation as attorney [sheet 4]
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 33005588224
    Then I am taken to "/lpa/33005588224/date-check"
    And I can see a reminder to sign continuation sheet 4

  Scenario: An LPA with trust corporations as primary and replacement attorney [sheet 4]
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 33005588225
    Then I am taken to "/lpa/33005588225/date-check"
    And I can see a reminder to sign continuation sheet 4
