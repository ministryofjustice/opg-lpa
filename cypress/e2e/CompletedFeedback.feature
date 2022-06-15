Feature: Completed Feedback

  I want to be able to visit the Completed Feedback page

  Background:
    Given I ignore application exceptions

  @focus
  Scenario: Visit completed feedback page
    Given I visit "/completed-feedback"
    Then I see "Send us feedback" in the page text
