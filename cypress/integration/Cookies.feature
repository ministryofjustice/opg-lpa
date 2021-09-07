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

    @focus
    Scenario: Accepting analytics cookies then rejecting analytics cookies fully removes them (LPAL-486)
        Given I visit "/home"
        When I visit link in new tab containing "View cookies"
        Then I click "usageCookies-yes"
        And I click "cookies-save"
        And "usageCookies-yes" is checked
        When I click "usageCookies-no"
        Then I click "cookies-save"
        And "usageCookies-no" is checked
        Then analytics cookies are not set

    @focus
    Scenario: Accepting analytics cookies in banner sets analytics cookies on client (LPAL-480)
        Given I visit "/home"
        When I click "accept-analytics-cookies"
        Then analytics cookies are set
        And I see a message that I have "accepted" analytics cookies
        And I can see a hide button to close the cookies banner
        When I click "hide-cookies-banner"
        Then the cookie banner is not visible

    @focus
    Scenario: Rejecting analytics cookies in banner does not set analytics cookies on client (LPAL-480)
        Given I visit "/home"
        When I click "reject-analytics-cookies"
        Then analytics cookies are not set
        And I see a message that I have "rejected" analytics cookies
        And I can see a hide button to close the cookies banner
        When I click "hide-cookies-banner"
        Then the cookie banner is not visible
