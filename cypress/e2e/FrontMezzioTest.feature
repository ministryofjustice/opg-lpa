@FrontMezzio
Feature: Front Mezzio Login Page

  I want to be able to visit the front mezzio login page

  Scenario: Visit login page
    Given I visit "/login"
    Then I see "bob" in the title
