@Session
Feature: Session

    I want to be notified when my session is about to expire

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: Session timeout pop-up displays after inactivity and keeps focus (LPAL-245)
        # Session lifetime is set in the test env in docker-compose.yml
        # via the OPG_LPA_AUTH_TOKEN_TTL env var
        Given I log in as appropriate test user
        Then I see "Make a lasting power of attorney" in the page text
        # The wait here should be equal to
        # OPG_LPA_AUTH_TOKEN_TTL - 300 (5 minutes);
        # this ensures we are on the page for enough time to reach
        # < 5 minutes remaining in our session, which is what prompts the
        # session timeout to show
        Then I should not see "Session timeout" in the page text
        When I wait for 35 seconds
        Then I see "Session timeout" in the page text
        And there are "two" "button" elements inside "session-timeout-form"
        # Pressing tab twice will take the user outside the timeout pop-up
        # if focus is not being held within it; likewise, shift+tab will take
        # the user outside (in the backwards direction)
        When I press tab
        Then my focus is within "session-timeout-form"
        When I press tab
        Then my focus is within "session-timeout-form"
        When I press shift+tab
        Then my focus is within "session-timeout-form"
        When I press shift+tab
        Then my focus is within "session-timeout-form"
