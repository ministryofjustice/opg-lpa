@RealOneLogin
Feature: Sign in through the real GOV.UK One Login integration environment
  As the Make team
  I want to know that signing in through GOV.UK One Login itself still works
  So that changes to One Login's journey or our client configuration are caught before go-live

  Scenario: Signing in through GOV.UK One Login returns the user to Make
    Given I visit "/login"
    When I sign in through GOV.UK One Login integration with the test account
    Then I am back in Make from GOV.UK One Login
