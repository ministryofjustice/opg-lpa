Feature: Homepage

  I want to be able to visit the dashboard and reuse LPA details

  Background:
    Given I ignore application exceptions

  @focus, @CleanupFixtures, @Reusable
  Scenario: PF LPA is reusable after skipping replacement attorneys, certificate provider and people to notify (LPAL-64)
    Given I create PF LPA test fixture with a donor and attorneys
    And I log in as appropriate test user

    # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove
    And I visit the replacement attorney page for the test fixture lpa
    And I click "save"
    And I am taken to the certificate provider page for the test fixture lpa
    And I click "skip-certificate-provider"

    When I visit the dashboard
    Then I cannot see a "Reuse LPA details" link for the test fixture lpa

    Given I visit the people to notify page for the test fixture lpa
    And I click "save"
    When I visit the dashboard
    Then I can see a "Reuse LPA details" link for the test fixture lpa
    And I visit the replacement attorney page for the test fixture lpa