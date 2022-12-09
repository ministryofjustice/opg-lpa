@Ping
Feature: Ping

    I want to be able to healthcheck the LPA service

    @focus
    Scenario: Healthcheck LPA service, HTML version
        When I visit "/ping"
        Then I see "Database is up and running" in the page text

    @focus
    Scenario: Healthcheck LPA service, JSON version
        When I visit the "/ping/json" JSON endpoint and save the response as "@ping"
        Then I should have a valid JSON response saved as "@ping"
        And the object "@ping" should have these properties:
          | body.dynamo.ok               | true |
          | body.api.details.database.ok | true |
          | body.ordnanceSurvey.ok       | true |
