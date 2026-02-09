@FrontMezzio
Feature: Front Mezzio Login Page

  I want to be able to visit the front mezzio login page

  Scenario: Visit login page
    Given I visit "/login"
    Then I can find "login-email"
    And I can find "login-password"

  Scenario: Visit dashboard page
    Given I visit "/dashboard"
