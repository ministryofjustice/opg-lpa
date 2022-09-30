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
    And I can see fields for the donor "Mr Dead Pool", certificate provider "Mr Cee Pee", attorney "Mr A Att", and applicant "Mr Dead Pool"
    And I cannot see continuation sheet reminders
    And I can see that the donor "Mr Dead Pool" can sign

  Scenario: Displays relevant information on signing continuation sheets
    Given I visit the dashboard
    And I click "check-signing-dates" for LPA ID 26997335999
    Then I am taken to "/lpa/26997335999/date-check"
    # Additional attorneys (cs1), additional preferences (2), donor cannot sign or make mark (3)
    And I can see a reminder to sign continuation sheet 1, 2 and 3
    # primaryAttorney is a trust corporation (4)
    And I can see a reminder to sign continuation sheet 4
    And I can see that the donor "Mr Christopher Robin" cannot sign

  Scenario: Displays the correct references to donor/applicant in errors when they cannot sign
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 26997335999
    And I click element marked "Check dates"
    And the visually-hidden legend for "date-check-donor-date" states "Check signature dates for the person who signed on behalf of the donor"
    Then I can see validation errors refer to the person signing on behalf of the donor, who is also the applicant

  Scenario: Displays the correct references to donor/applicant in errors when they can sign
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 91155453023
    And I click element marked "Check dates"
    And the visually-hidden legend for "date-check-donor-date" states "Check signature dates for the donor"
    Then I can see validation errors refer to the donor, who is also the applicant

  Scenario: Displays the correct references to applicant in errors when they are the donor and cannot sign
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 26997335999
    Then I can see that a person is signing on behalf of the applicant "Mr Christopher Robin"

    # applicant signs in the future
    When I fill in all signature dates on the check dates form
    And I fill in the "date-check-applicant" signature dates with "tomorrow"
    And I click element marked "Check dates"
    Then I can see applicant validation errors about person signing on behalf of the applicant not signing in the future

    # applicants sign before attorneys
    When I fill in all signature dates on the check dates form
    And I fill in the "date-check-primary-attorney" signature dates with "today"
    And I fill in the "date-check-applicant" signature dates with "yesterday"
    And I click element marked "Check dates"
    Then I can see applicant validation errors about person signing on behalf of applicant not signing before attorneys

  Scenario: Certificate provider error message if they sign before the donor
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 88668805824
    And I fill in the "date-check-donor" signature dates with "today"
    And I fill in the "date-check-primary-attorney" signature dates with "today"
    And I fill in the "date-check-applicant" signature dates with "today"
    And I fill in the "date-check-certificate" signature dates with "yesterday"
    And I click element marked "Check dates"
