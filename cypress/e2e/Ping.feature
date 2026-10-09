@Ping
Feature: Ping

    I want to be able to healthcheck the LPA service

    @focus
    Scenario: Health check
        When I visit the "/health-check" JSON endpoint and save the response as "@checkJson"
        Then I should have a valid JSON response saved as "@checkJson"
        And the object "@checkJson" should have these properties:
          | body.ok    | true |

    @focus
    Scenario: Service health check
        When I visit the "/health-check/service" JSON endpoint and save the response as "@serviceJson"
        Then I should have a valid JSON response saved as "@serviceJson"
        And the object "@serviceJson" should have these properties:
          | body.sessionSaveHandler.ok   | true |
          | body.dynamo.ok               | true |
          | body.api.details.database.ok | true |

    @focus
    Scenario: Dependencies health check
        When I visit the "/health-check/dependencies" JSON endpoint and save the response as "@dependenciesJson1"
        Then the object "@dependenciesJson1" should have these values:
          | body.mail.ok           | true |
          | body.ordnanceSurvey.ok | true |

        # page refresh to check the OS response is the cached one
        When I visit the "/health-check/dependencies" JSON endpoint and save the response as "@dependenciesJson2"
        Then the object "@dependenciesJson2" should have these values:
          | body.mail.ok               | true |
          | body.ordnanceSurvey.ok     | true |
          | body.ordnanceSurvey.cached | true |

    @focus
    Scenario: Healthcheck LPA service, XML/Pingdom version
        When I visit the "/ping/pingdom" XML endpoint and save the response as "@pingXml"
        Then I should have a valid XML response saved as "@pingXml"
