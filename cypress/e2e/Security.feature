Feature: HTML and JS prevent common exploits

    Background:
        Given I ignore application exceptions

    # Links which open new tabs have noreferrer and noopener

    @RunLinkCheckAfterStep
    Scenario: Accessibility statement
        When I visit "/home"
        Then I visit link with text "Accessibility statement" in a new tab
        # And the RunLinkCheckAfterStep passes without errors

    @RunLinkCheckAfterStep
    Scenario: Privacy statement
        When I visit "/home"
        Then I visit link with text "Privacy notice" in a new tab
        # And the RunLinkCheckAfterStep passes without errors

    @RunLinkCheckAfterStep
    Scenario: Terms of use statement
        When I visit "/home"
        Then I visit link with text "Terms of use" in a new tab
        # And the RunLinkCheckAfterStep passes without errors

    @RunLinkCheckAfterStep
    Scenario: Create account page
        When I visit "/home"
        Then I visit link containing "Create my account"
        # And the RunLinkCheckAfterStep passes without errors
