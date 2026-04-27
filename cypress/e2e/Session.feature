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
        # Verify the popup is a native modal dialog - the browser enforces the
        # focus trap, so we test our code: correct element type, opened as a modal,
        # and that focus is placed on the heading on open (custom code in showWarning())
        And "timeout-popup" is a modal dialog
        And "timeout-dialog-title" is the active element

    Scenario: Session timeout displays after inactivity with revised message (LPAL-905)
        Given I log in as appropriate test user
        Then I see "Make a lasting power of attorney" in the page text
        Then I should not see "Session timeout" in the page text

        When I hack the session to have 0 seconds remaining
        And I wait for 3 seconds
        And I visit "/user/about-you"

        Then I see "We’ve signed you out" in the page text
        And I see "To continue, sign in again" in the page text
