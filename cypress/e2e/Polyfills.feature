# TODO: We don't need this feature as it was a polyfill for old govuk_elements - LPAL-2492 will remove
@Polyfills
Feature: Polyfills

  I want to be able to visit the site in an old browser

  Background:
    Given I ignore application exceptions

  @focus
  Scenario: Navigate polyfilled details elements on home page using keyboard (LPAL-253)
    Given I visit "/home?show-old-homepage"
    When my browser doesn't support details elements
    Then I can navigate through "polyfilleddetails" elements using the tab key
