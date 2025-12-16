Feature: Stats Page

  I want to be able to see that the stats page is working

  Scenario: Visit home page links
    Given I visit "/stats"
    Then I see "LPA Statistics" in the title
