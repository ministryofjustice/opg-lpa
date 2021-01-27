@Guidance
Feature: Guidance

  I want to be able to read guidance about how to use the tool

  # test to verify LPAL-247
  #
  # screen width must be > 640, otherwise help displays in main window
  # rather than an overlay; see moj.help-system.js, which switches off
  # JS links when on a mobile width screen
  @focus
  Scenario: Open guidance and navigate with keyboard in non-mobile browser
    Given I log in as appropriate test user
    And I am using a viewport greater than 640 pixels wide
    When I visit "/home"
    And I click "guidance"
    And I wait for focus on "popup-close"
    And I press tab
    Then I am focused on "donor-link"
