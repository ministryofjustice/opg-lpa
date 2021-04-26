@Guidance
Feature: Guidance

  I want to be able to read guidance about how to use the tool

  # as we can't zoom the browser from JavaScript, instead make the browser
  # viewport really narrow to force elements to overflow
  @focus
  Scenario: In a narrow viewport, email text should not overflow the footer (LPAL-255)
    Given I log in as appropriate test user
    And I am using a viewport which is 400 pixels wide
    When I visit "/home"
    And I click "guidance"
    Then the text in "help-footer-contacts" does not overflow
