@Errors
Feature: Errors

    I want to be see useful and relevant error messages

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: Error message heading level and text / server-rendered (LPAL-247)
        Given I visit "/send-feedback"
        When I submit the feedback
        Then I see "There is a problem" in the page text
        And I see "Error" in the title
        And "error-heading" is a "level 2 heading" element
        And there is "one" "level 1 heading" element on the page

    @focus
    # requires additional scenario as login page doesn't use the error macro
    Scenario: Error message heading level and text / server-rendered auth (LPAL-247)
        Given I visit "/login"
        And I type "foo@example.com" into "login-email"
        And I type "aaa" into "login-password"
        And I click "login-submit-button"
        Then I see "There is a problem" in the page text
        And I see "Error" in the title
        And "error-heading" is a "level 2 heading" element
        And there is "one" "level 1 heading" element on the page

    @focus
    Scenario: Fail to select type of LPA to create, error links to first radio (LPAL-248)
        When I log in as appropriate test user
        And If I am on dashboard I click to create lpa
        And I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose a type of LPA |
        When I visit link containing "Choose a type of LPA"
        Then I am focused on "type-property-and-financial"
