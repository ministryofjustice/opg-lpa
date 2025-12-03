Feature: Homepage

  I want to be able to visit the homepage and follow the links

  Scenario: Visit home page links
    Given I visit "/home"
    Then I see that included assets such as js and css are ok
    And I see "Make a lasting power of attorney" in the title
    When I visit link in new tab containing "Terms of use"
    Then I am taken to "/terms"
    And I see "Terms of use" in the title
    And I should encounter a visually-hidden statement about links on the page opening in new tabs
    And I should not find links in the page which open in new tabs without notifying me
    When I visit link in new tab containing "Privacy notice"
    Then I am taken to "/privacy-notice"
    And I see "Privacy notice" in the title
    And I should encounter a visually-hidden statement about links on the page opening in new tabs
    And I should not find links in the page which open in new tabs without notifying me
    When I visit link in new tab containing "View cookies"
    Then I am taken to "/cookies"
    And I see "Cookies" in the title
    And I should not find links in the page which open in new tabs without notifying me
    #When I click back
    #And I visit link named "a.js-guidance"

  Scenario: Use skip link on home page
    Given I visit "/home"
    When I disable stylesheets
    And I click "skip-link"
    Then I have "main-title" in the viewport
    And I do not have "banner" in the viewport
    And I do not have "cookie-message" in the viewport

  Scenario: Navigate details elements on home page using keyboard (LPAL-253)
    Given I visit "/home"
    Then I can navigate through "details" elements using the tab key

  Scenario: Sufficient contrast on home page elements (LPAL-256)
    Given I visit "/home"
    #  When I wait for focus on "guidance-to-making-an-lpa-link"
    # Then elements on the page should have sufficient contrast
    # When I wait for focus on "sign-in-button"
    # Then elements on the page should have sufficient contrast

  Scenario: Check Response headers are present and correct
    Given I verify that the homepage response contains all the required headers
