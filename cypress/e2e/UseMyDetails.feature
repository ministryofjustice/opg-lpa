Feature: UseMyDetails

  I want to be able to use my details for the donor

  # Effectively a regression test for LPAL-2056

  Background:
    Given I ignore application exceptions
    And I log in as seeded user

  Scenario: Searching for an LPA by donor name returns multiple results (LPAL-539)
    Given I visit the dashboard
    When I click "createnewlpa"
    When I choose Property and Finance
    And I click "save"
    Then I am taken to the donor page
    When I click "add-donor"
    Then I can find "form-donor"
    When I visit link containing "Use my details"
    Then I see "name-title" prepopulated with "Mr"
    And I see "name-first" prepopulated with "Test"
    And I see "name-last" prepopulated with "User"
    And I see "email-address" prepopulated with "seeded_test_user@digital.justice.gov.uk"
    And I see "dob-date-day" prepopulated with "28"
    And I see "dob-date-month" prepopulated with "11"
    And I see "dob-date-year" prepopulated with "1982"
    And I see "address-address1" prepopulated with "THE OFFICE OF THE PUBLIC GUARDIAN"
    And I see "address-address2" prepopulated with "THE AXIS"
    And I see "address-address3" prepopulated with "10 HOLLIDAY STREET, BIRMINGHAM"
    And I see "address-postcode" prepopulated with "B1 1TF"
