@Ping
Feature: Ping

    I want to be able to healthcheck the LPA service

    Scenario: Healthcheck LPA service
        Given I visit the ping page
        Then I see "Database is up and running" in the page text
