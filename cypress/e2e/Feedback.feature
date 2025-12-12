# If exapnding this test, consider if we should make rate limit configurable to reduce wait times (currently 3 seconds)
Feature: Feedback

    I want to be able to provide feedback

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: Visit feedback
        # exact copy of 99-feedback Casper test
        Given I visit "/home"
        When I visit link containing "feedback"
        Then I am taken to "/send-feedback"
        And I see "Send us feedback" in the title
        And I can find feedback buttons
        And I wait for 3 seconds
        When I submit the feedback
        Then I see in the page text
            | There is a problem |
            | Select a rating for this service |
            | Do not forget to leave your feedback in the box |
        And I see "Error" in the title
        # note-  extra testing of clicking radion buttons due to historic bug LPAL-1038
        When I select rating "very-satisfied"
        Then I can see that rating "very-satisfied" is selected
        When I select rating "satisfied"
        Then I can see that rating "satisfied" is selected
        When I select rating "neither-satisfied-or-dissatisfied"
        Then I can see that rating "neither-satisfied-or-dissatisfied" is selected
        When I select rating "dissatisfied"
        Then I can see that rating "dissatisfied" is selected
        When I select rating "very-dissatisfied"
        Then I can see that rating "very-dissatisfied" is selected
        When I select rating "dissatisfied"
        Then I can see that rating "dissatisfied" is selected
        When I select rating "neither-satisfied-or-dissatisfied"
        Then I can see that rating "neither-satisfied-or-dissatisfied" is selected
        When I select rating "satisfied"
        Then I can see that rating "satisfied" is selected
        When I select rating "very-satisfied"
        Then I can see that rating "very-satisfied" is selected
        When I select rating "neither-satisfied-or-dissatisfied"
        Then I can see that rating "neither-satisfied-or-dissatisfied" is selected
        And I wait for 3 seconds
        And I submit the feedback
        Then I see in the page text
            | There is a problem |
            | Do not forget to leave your feedback in the box |
        And I can find "feedback-textarea" wrapped with error highlighting
        And I see "Error" in the title
        When I select rating "neither-satisfied-or-dissatisfied"
        Then I can see that rating "neither-satisfied-or-dissatisfied" is selected
        And I set feedback email as "cypress@opg-lpa-test.net"
        And I set feedback content as "Cypress feedback form test"
        Then I expect submitted feedback form to contain a rating of "neither-satisfied-or-dissatisfied"
        And I wait for 3 seconds
        And I submit the feedback
        Then I see "Thank you" in the title
        And I can find link pointing to "/home"

    @focus
    Scenario: Fail to select a rating for feedback, error links to first radio (LPAL-248)
        Given I visit "/send-feedback"
        And I wait for 3 seconds
        And I submit the feedback
        And I see "Select a rating for this service" in the page text
        # this is the link in the error summary
        When I visit link containing "Select a rating for this service"
        Then I am focused on "rating-very-satisfied"
