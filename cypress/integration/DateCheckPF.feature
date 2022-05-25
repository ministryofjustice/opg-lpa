Feature: Check signature dates for a PF LPA

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
    And I cannot see continuation sheet reminders

  Scenario: Displays relevant information on signing continuation sheets
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 33005588225
    Then I am taken to "/lpa/33005588225/date-check"
  # Donor cannot sign or make mark
    And I can see a reminder to sign continuation sheet 3 for PF
  # primaryAttorney and replacementAttorney are trust corporations
    And I can see a reminder to sign continuation sheet 4
