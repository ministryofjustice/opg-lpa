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

  Scenario Outline: A public form that creates or acts on a session rejects a missing CSRF token
    When I post to "<path>" without a CSRF token
    Then the request is rejected as a CSRF failure

    Examples:
      | path                 |
      | /login               |
      | /signup              |
      | /signup/resend-email |
      | /forgot-password     |
      | /send-feedback       |

  @RequiresOneLogin
  Scenario Outline: A One Login linking form rejects a missing CSRF token
    When I post to "<path>" without a CSRF token
    Then the request is rejected as a CSRF failure

    Examples:
      | path                    |
      | /link-account           |
      | /link-or-create-account |

  Scenario: Signing in with the wrong CSRF token is rejected
    When I post to "/login" with an invalid CSRF token
    Then the request is rejected as a CSRF failure
    And the CSRF error is shown

  Scenario: The sign-in page issues a usable token
    Given I visit "/login"
    Then the page carries a CSRF token
    And signing in with that token is not rejected as a CSRF failure
