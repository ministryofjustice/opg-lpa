Feature: Check signature dates

  # TO DO: tests missing for date check feature, current tests only cover messages displayed relating to continuation sheets [LPAL-835]

  Background:
    Given I ignore application exceptions
    And I log in as seeded user

  Scenario: View the Check Signature Dates tool
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 91155453023
    Then I am taken to "/lpa/91155453023/date-check"
    And I see "Check signature dates" in the title
    And there are "four" ".date-check-person" elements on the page
    And I can see fields for the donor, certificate provider, attorney, applicant
    And I cannot see continuation sheet reminders

  Scenario: Displays relevant information on signing continuation sheets
    Given I visit the dashboard
    When I visit the dashboard
    And I click "check-signing-dates" for LPA ID 26997335999
    Then I am taken to "/lpa/26997335999/date-check"
    # Additional attorneys and additional preferences
    And I can see a reminder to sign continuation sheet 1 and 2
    # Donor cannot sign or make mark
    And I can see a reminder to sign continuation sheet 3
    # primaryAttorney is a trust corporation
    And I can see a reminder to sign continuation sheet 4
