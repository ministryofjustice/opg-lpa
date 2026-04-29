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
