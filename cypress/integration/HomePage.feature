Feature: Homepage

  I want to be able to visit the homepage and follow the links

  @focus
  Scenario: Visit home page links
    Given I visit "/home"
    Then I see "Make a lasting power of attorney" in the title
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
    # for now we must click back to home because cookies link behaves differently from privacy page
    When I click back
    Then I visit link in new tab containing "Cookies"
    And I am taken to "/cookies"
    And I see "Cookies" in the title
    And I should not find links in the page which open in new tabs without notifying me
    #When I click back
    #And I visit link named "a.js-guidance"
