@Dashboard
Feature: Dashboard

  I want to be able to manage my LPAs on the dashboard

  # Important: We have to use a user with at least 6 LPAs for these tests.
  # (If a user has < 6 LPAs, they won't get a search box.) Hence
  # "log in as seeded user".

  Background:
    Given I ignore application exceptions
    And I log in as seeded user

  Scenario: Searching for an LPA by donor name returns multiple results (LPAL-539)
    Given I visit the dashboard
    When I type "Elsa" into "dashboard-search-box"
    And I click "dashboard-search-icon"
    Then I see "Elsa" in the page text
    And I see "Your search has found 2 results" in the page text
    And there are "two" "li[data-refresh-id]" elements on the page

  Scenario: Searching for an LPA by ID returns 1 result (LPAL-539)
    Given I visit the dashboard
    When I type "A911 5545 3023" into "dashboard-search-box"
    And I click "dashboard-search-icon"
    Then I see "A911 5545 3023" in the page text
    And I see "Your search has found 1 results" in the page text
    And there is "one" "li[data-refresh-id]" element on the page

  Scenario: Searching for an LPA with an unknown donor returns no results (LPAL-539)
    Given I visit the dashboard
    When I type "MebastianAhaVortigant" into "dashboard-search-box"
    And I click "dashboard-search-icon"
    Then I see "MebastianAhaVortigant" in the page text
    And I see "Your search has found no results" in the page text

  Scenario: Searching for an LPA with an unknown ID returns no results (LPAL-539)
    Given I visit the dashboard
    When I type "A999 9999 9999" into "dashboard-search-box"
    And I click "dashboard-search-icon"
    Then I see "A999 9999 9999" in the page text
    And I see "Your search has found no results" in the page text
