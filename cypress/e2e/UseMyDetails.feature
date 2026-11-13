Feature: UseMyDetails

  I want to be able to use my details for the donor

  # Effectively a regression test for LPAL-2056

  Background:
    Given I ignore application exceptions
    And I create a new user with 0 LPAs
    And I log in as the newly created fixture user

  Scenario: Searching for an LPA by donor name returns multiple results (LPAL-539)
    Given I visit the dashboard
    When I click "createnewlpa"
    When I choose Property and Finance
    And I click "save"
    Then I am taken to the donor page
    When I click "add-donor"
    Then I can find "form-donor"
    When I visit link containing "Use my details"
    Then I see "name-title" prepopulated with "Dr"
    And I see "name-first" prepopulated with "Fixture"
    And I see "name-last" prepopulated with "User"
    And I see "email-address" prepopulated with the fixture user's email
    And I see "dob-date-day" prepopulated with "22"
    And I see "dob-date-month" prepopulated with "10"
    And I see "dob-date-year" prepopulated with "1988"
    And I see "address-address1" prepopulated with "Bank End Farm House"
    And I see "address-address2" prepopulated with "Undercliff Drive"
    And I see "address-address3" prepopulated with "Ventnor, Isle of Wight"
    And I see "address-postcode" prepopulated with "PO38 1UL"
