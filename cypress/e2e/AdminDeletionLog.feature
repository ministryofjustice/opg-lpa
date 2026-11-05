@AdminDeletionLog
Feature: Admin deletion log

  I want to be able to delete the deletion log of a deleted account from the admin search page

  Background:
    Given I log in to admin using SSO
    And a deletion log exists for "deletion-log-{random}@example.com"
    When I search for the deletion log email
    Then I see the delete log link

  Scenario: Cancelling returns to search without deleting the log
    When I click "delete-log-link"
    Then I should be on admin page matching "/deletion-log/delete"
    And I see "This action cannot be reversed" in the page text
    And I see the deletion log email on the delete page
    When I click "cancel-button"
    Then I should be on admin page matching "/search"
    When I search for the deletion log email
    Then I see the delete log link

  Scenario: Deleting the log shows a success banner
    When I click "delete-log-link"
    And I click "delete-log-button"
    Then I should be on admin page matching "/search"
    And I see a success banner mentioning the deletion log email
    When I search for the deletion log email
    Then I do not see the delete log link
