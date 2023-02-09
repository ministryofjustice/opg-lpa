@Ping
Feature: Ping

    I want to be able to healthcheck the LPA service

    @focus
    Scenario: Healthcheck LPA service, HTML version
        When I visit "/ping"
        Then I see "Database is up and running" in the page text
        And I see "Ordnance Survey postcode lookup is up and running" in the page text

        # page refresh to check cache message is displayed for OS
        When I reload the page
        Then I see "The Ordnance Survey status is a cached response due to rate limiting" in the page text

    @focus
    Scenario: Healthcheck LPA service, JSON version
        When I visit the "/ping/json" JSON endpoint and save the response as "@pingJson1"
        Then I should have a valid JSON response saved as "@pingJson1"
        And the object "@pingJson1" should have these properties:
          | body.dynamo.ok               | true |
          | body.api.details.database.ok | true |
          | body.ordnanceSurvey.ok       | true |

        # page refresh to check the OS response is the cached one
        When I visit the "/ping/json" JSON endpoint and save the response as "@pingJson2"
        Then the object "@pingJson2" should have these values:
          | body.ordnanceSurvey.ok       | true |
          | body.ordnanceSurvey.cached   | true |

    @focus
    Scenario: Healthcheck LPA service, XML/Pingdom version
        When I visit the "/ping/pingdom" XML endpoint and save the response as "@pingXml"
        Then I should have a valid XML response saved as "@pingXml"
