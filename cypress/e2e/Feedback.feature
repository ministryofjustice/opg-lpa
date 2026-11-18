# If exapnding this test, consider if we should make rate limit configurable to reduce wait times (currently 3 seconds)
Feature: Feedback

    I want to be able to provide feedback

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: Provide feedback
        Given I visit "/home"
        When I visit link containing "feedback"
        Then I am taken to "/send-feedback"
        And I see "Send us feedback" in the title
        And I wait for 3 seconds
        When I submit the feedback
        Then I see in the page text
            | There is a problem |
            | Select a rating for this service |
            | Do not forget to leave your feedback in the box |
        And I see "Error" in the title
        When I select rating "neither-satisfied-or-dissatisfied"
        And I wait for 3 seconds
        And I submit the feedback
        Then I see in the page text
            | There is a problem |
            | Do not forget to leave your feedback in the box |
        And I can find "feedback-textarea" wrapped with error highlighting
        And I see "Error" in the title
        When I select rating "neither-satisfied-or-dissatisfied"
        And I set feedback email as "cypress@opg-lpa-test.net"
        And I set feedback content as "Cypress feedback form test"
        Then I expect submitted feedback form to contain a rating of "neither-satisfied-or-dissatisfied"
        And I wait for 3 seconds
        And I submit the feedback
        Then I see "Thank you" in the title
        And I can find link pointing to "/home"

    Scenario: Double clicking the feedback submit button only submits the form once
        Given I visit "/send-feedback"
        And I select rating "satisfied"
        And I set feedback content as "Cypress double click test"
        And I wait for 3 seconds
        Then I double click "feedback-submit-button" and the second click is prevented
