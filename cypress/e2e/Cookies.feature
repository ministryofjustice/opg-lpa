Feature: Cookies

    I want to be able to manage cookie preferences

    Background:
        Given I ignore application exceptions

    Scenario: Legend for cookie management is positioned correctly for screen readers (LPAL-241)
        Given I visit "/home"
        When I visit link in new tab containing "View cookies"
        Then there is "one" "legend" element inside "cookies-fieldset"
        And there are "two" "input[type=radio]" elements inside "cookies-fieldset"

    @RunLinkCheckAfterStep
    Scenario: Accepting analytics cookies then rejecting them removes them from client (LPAL-486)
        Given I visit "/home"
        When I visit link in new tab containing "View cookies"
        And I click "usageCookies-yes"
        And I click "cookies-save"
        And I see "You’ve set your cookie preferences." in the page text

        Then I visit "/cookies"
        And "usageCookies-yes" is checked
        And analytics cookies are set

        When I click "usageCookies-no"
        And I click "cookies-save"
        And I see "You’ve set your cookie preferences." in the page text

        Then I visit "/cookies"
        And "usageCookies-no" is checked
        And analytics cookies are not set

    Scenario: Accepting analytics cookies in banner sets analytics cookies on client (LPAL-480)
        Given I visit "/home"
        When I click "accept-analytics-cookies"
        And I see a message that I have "accepted" analytics cookies
        And I can see a hide button to close the cookies banner
        When I click "hide-cookies-banner"
        Then the cookie banner is not visible
        When I reload the page
        Then analytics cookies are set

    Scenario: Rejecting analytics cookies in banner does not set analytics cookies on client (LPAL-480)
        Given I visit "/home"
        When I click "reject-analytics-cookies"
        And I see a message that I have "rejected" analytics cookies
        And I can see a hide button to close the cookies banner
        When I click "hide-cookies-banner"
        Then the cookie banner is not visible
        When I reload the page
        Then analytics cookies are not set

        # ensure that the cookies stay unset when we navigate the site
        When I visit "/home"
        Then analytics cookies are not set
