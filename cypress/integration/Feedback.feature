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
        When I submit the feedback
        Then I see "There was a problem submitting your feedback" in the page text
        When I select satisfied
        And I submit the feedback
        Then I see "There was a problem submitting your feedback" in the page text
        When I select neither satisfied or dissatisfied
        And I set feedback email as "cypress@opg-lpa-test.net"
        And I set feedback content as "Cypress feedback form test"
        And I submit the feedback
        Then I see "Thank you" in the title
        And I can find link pointing to "/home"
