Feature: HTML and JS prevent common exploits

    Background:
        Given I ignore application exceptions

    # Links which open new tabs have noreferrer and noopener

    @RunLinkCheckAfterStep
    Scenario: Accessibility statement
        When I visit "/home"
        Then I visit link with text "Accessibility statement" in a new tab

    @RunLinkCheckAfterStep
    Scenario: Privacy statement
        When I visit "/home"
        Then I visit link with text "Privacy notice" in a new tab

    @RunLinkCheckAfterStep
    Scenario: Terms of use statement
        When I visit "/home"
        Then I visit link with text "Terms of use" in a new tab

    @RunLinkCheckAfterStep
    Scenario: Cookies page
        When I visit "/home"
        Then I visit link containing "Cookies"
