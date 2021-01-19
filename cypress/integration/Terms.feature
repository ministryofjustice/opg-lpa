@Terms
Feature: Terms

    I want to understand the site's terms and conditions

    Scenario: Visit terms
        Given I visit "/terms"
        Then I should encounter a visually-hidden statement about links on the page opening in new tabs
