@MezzioHome
Feature: Mezzio Home Page

  I want to be able to visit the mezzio app home page

  Scenario: Visit home page
    Given I visit "/home"
    Then I can find "sign-in-button"
    And I can find "guidance-to-making-an-lpa-link"
