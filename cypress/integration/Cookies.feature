Feature: Cookies

    I want to be able to manage cookie preferences

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: Legend for cookie management is positioned correctly for screen readers (LPAL-241)
        Given I visit "/home"
        When I visit link in new tab containing "View cookies"
        Then there is "one" "legend" element inside "cookies-fieldset"
        And there are "two" "input[type=radio]" elements inside "cookies-fieldset"
