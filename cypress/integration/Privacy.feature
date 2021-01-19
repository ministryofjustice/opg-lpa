@Privacy
Feature: Privacy

    I want to understand the site's privacy notice

    Scenario: Visit privacy
        Given I visit "/privacy-notice"
        Then I should encounter a visually-hidden statement about links on the page opening in new tabs
