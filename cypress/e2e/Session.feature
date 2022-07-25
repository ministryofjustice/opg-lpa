@Session
Feature: Session

    I want to be notified when my session is about to expire

    Background:
        Given I ignore application exceptions

    Scenario: Session timeout warning pop-up displays after inactivity and keeps focus (LPAL-245)
        Given I log in as appropriate test user
        Then I see "Make a lasting power of attorney" in the page text
        Then I should not see "Session timeout" in the page text

        # The remaining session seconds here should be equal to
        # 300 + a few seconds; this ensures we are on the page for enough time
        # to reach < 5 minutes remaining in our session, which is what prompts the
        # session timeout to show
        When I hack the session to have 302 seconds remaining
        And I verify that the session has at most 302 seconds remaining
        And I visit "/user/about-you"

        # This should be the same as the session remaining seconds - 299;
        # if we wait this long, we enter into the window of time where the
        # impending session timeout dialog appears
        And I wait for 3 seconds

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

    Scenario: Session timeout displays after inactivity with revised message (LPAL-905)
        Given I log in as appropriate test user
        Then I see "Make a lasting power of attorney" in the page text
        Then I should not see "Session timeout" in the page text

        When I hack the session to have 0 seconds remaining
        And I wait for 3 seconds
        And I visit "/user/about-you"

        Then I see "We’ve signed you out" in the page text
        And I see "To continue, sign in again" in the page text
