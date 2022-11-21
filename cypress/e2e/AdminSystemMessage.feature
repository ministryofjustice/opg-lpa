@AdminSystemMessage
Feature: AdminSystemMessage

  I want to be able to visit the admin page, set a system message, and have it safely rendered in the user-facing site (LPAL-1022)

  Scenario: Set a system message
    Given I log in to admin
    And I click "system-message-link"
    Then I am taken to the system message page
    When I type "<script>document.body.innerHTML = 'gotcha';</script>Bona fide message here" into "message"
    And I click "submit-button"
    Then I see "System message set" in the page text

  Scenario: System message should be set on user-facing site, with HTML escaped
    When I visit "/home"
    Then I see "document.body.innerHTML = 'gotcha';Bona fide message here" in the page text

  Scenario: Remove system message
    Given I log in to admin
    And I click "system-message-link"
    When I clear the value in "message"
    And I click "submit-button"
    Then I see "System message removed" in the page text
