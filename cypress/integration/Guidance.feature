@Guidance
Feature: Guidance

  I want to be able to read guidance about how to use the tool

  # screen width must be > 640, otherwise help displays in main window
  # rather than an overlay; see moj.help-system.js, which switches off
  # JS links when on a mobile width screen

  # after opening help, tab takes user into content not menu
  @focus
  Scenario: Tab navigation in guidance moves from content into menu (LPAL-247)
    Given I log in as appropriate test user
    And I am using a viewport greater than 640 pixels wide
    When I visit "/home"
    And I click "guidance"
    And I wait for focus on "popup-close"
    And I press tab
    Then I am focused on "donor-link"

  # shift focus back to content after clicking topic link
  @focus
  Scenario: After clicking guidance topic, focus is in help content (LPAL-247)
    Given I log in as appropriate test user
    And I am using a viewport greater than 640 pixels wide
    When I visit "/home"
    And I click "guidance"
    And I wait for focus on "popup-close"
    And I click "preferences-and-instructions-nav-link"
    And I press tab
    Then I am focused on "replacement-attorneys-link"
