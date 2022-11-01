Feature: Check signature dates

  # TO DO: tests missing for date check feature, current tests only cover messages displayed relating to continuation sheets [LPAL-835]

  Background:
    Given I ignore application exceptions
    And I log in as seeded user

  Scenario: View the Check Signature Dates tool from the dashboard
    Given I visit the dashboard
    And I type "A911 5545 3023" into "dashboard-search-box"
    And I click "dashboard-search-icon"
    When I click "check-signing-dates" for LPA ID 91155453023
    Then I am taken to "/lpa/91155453023/date-check"
    And I see "Check signature dates" in the title
    And there are "four" ".date-check-person" elements on the page
    And I can see fields for the donor "Mr Dead Pool", certificate provider "Mr Cee Pee", attorney "Mr A Att", and applicant "Mr Dead Pool"
    And I cannot see continuation sheet reminders
    And I can see that the donor "Mr Dead Pool" can sign

  Scenario: Displays relevant information on signing continuation sheets
    Given I visit "/lpa/26997335999/date-check"
    # Additional attorneys (cs1), additional preferences (2), donor cannot sign or make mark (3)
    Then I can see a reminder to sign continuation sheet 1, 2 and 3
    # primaryAttorney is a trust corporation (4)
    And I can see a reminder to sign continuation sheet 4
    And I can see that the donor "Mr Christopher Robin" cannot sign

  Scenario: Displays the correct references to donor/applicant in errors when they cannot sign
    Given I visit "/lpa/26997335999/date-check"
    When I click element marked "Check dates"
    Then the visually-hidden legend for "date-check-donor-date" states "Check signature dates for the person who signed on behalf of the donor"
    And I can see validation errors refer to the person signing on behalf of the donor, who is also the applicant

  Scenario: Displays the correct references to donor/applicant in errors when they can sign
    Given I visit "/lpa/91155453023/date-check"
    When I click element marked "Check dates"
    Then the visually-hidden legend for "date-check-donor-date" states "Check signature dates for the donor"
    And I can see validation errors refer to the donor, who is also the applicant

  Scenario: Displays the correct references to applicant in errors when they are the donor and cannot sign
    Given I visit "/lpa/26997335999/date-check"
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

  Scenario: Appropriate error messages are shown when signature dates are in the future
    Given I visit "/lpa/88668805824/date-check"

    # donor date in future
    When I fill in all signature dates on the check dates form
    And I fill in the "date-check-donor" signature dates with "tomorrow"
    And I click element marked "Check dates"
    Then I can see a warning that the donor's signature date cannot be in the future

    # primary attorney date in future
    When I fill in all signature dates on the check dates form
    And I fill in the "date-check-primary-attorney" signature dates with "tomorrow"
    And I click element marked "Check dates"
    Then I can see a warning that the primary attorneys' signature dates cannot be in the future

    # certificate provider date in future
    When I fill in all signature dates on the check dates form
    And I fill in the "date-check-certificate" signature dates with "tomorrow"
    And I click element marked "Check dates"
    Then I can see a warning that the certificate provider's signature date cannot be in the future

    # applicant date in future
    When I fill in all signature dates on the check dates form
    And I fill in the "date-check-applicant" signature dates with "tomorrow"
    And I click element marked "Check dates"
    Then I can see a warning that the applicant's signature date cannot be in the future

  Scenario: Certificate provider error messages are shown if they signed before a primary attorney
    Given I visit "/lpa/88668805824/date-check"
    When I fill in the "date-check-donor" signature dates with "11 days ago"
    And I fill in the "date-check-primary-attorney" signature dates with "11 days ago"
    And I fill in the "date-check-applicant" signature dates with "9 days ago"
    And I fill in the "date-check-certificate" signature dates with "12 days ago"
    And I click element marked "Check dates"
    Then I can see errors about the certificate provider not signing before the donor

  Scenario: Primary attorney error messages are shown if they signed before the donor
    Given I visit "/lpa/88668805824/date-check"
    When I fill in the "date-check-donor" signature dates with "today"
    And I fill in the "date-check-primary-attorney" signature dates with "yesterday"
    And I fill in the "date-check-applicant" signature dates with "today"
    And I fill in the "date-check-certificate" signature dates with "today"
    And I click element marked "Check dates"
    Then I can see primary attorney errors explaining that the donor must be the first person to sign

  Scenario: Applicant error messages are shown if they signed before the donor
    Given I visit "/lpa/88668805824/date-check"
    When I fill in the "date-check-donor" signature dates with "today"
    And I fill in the "date-check-primary-attorney" signature dates with "today"
    And I fill in the "date-check-certificate" signature dates with "today"
    And I fill in the "date-check-applicant" signature dates with "yesterday"
    And I click element marked "Check dates"
    Then I can see applicant errors explaining that the donor must be the first person to sign
    And I can see applicant errors explaining that the applicant must sign on or after date when section 11s were signed

  Scenario: Donor error messages are shown if section 9 (continuation sheet 3) is signed before section 5
    Given I visit "/lpa/68582508781/date-check"
    When I fill in the "date-check-donor-life-date" signature dates with "today"
    And I fill in the "date-check-donor-date" signature dates with "yesterday"
    And I fill in the "date-check-primary-attorney" signature dates with "today"
    And I fill in the "date-check-certificate" signature dates with "today"
    And I fill in the "date-check-applicant" signature dates with "today"
    And I click element marked "Check dates"
    Then I can see donor errors explaining that the donor must sign section 9 after section 5
