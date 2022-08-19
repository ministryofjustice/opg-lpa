@CorrespondentReuse
Feature: Reusable correspondent from cloned LPA

  I want to be able to reuse correspondent details from a cloned LPA for the correspondent in the clone LPA

  Background:
    Given I ignore application exceptions

  @focus @CleanupFixtures
  Scenario: Reused LPA's correspondent can be used as the correspondent on the cloned LPA (LPAL-522)
    # This is the LPA we're going to reuse
    Given I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent

    And I log in as appropriate test user
    And I visit the dashboard

    # An LPA only becomes reusable if the "save" button has been clicked on the people to notify page;
    # this sets the metadata on the LPA application which records that this stage has been reached,
    # which is what we use to decide whether to show the "Reuse" link. So click that button here.
    And I visit the people to notify page for the test fixture lpa
    And I click "save"
    When I visit the dashboard
    Then I can see a "Reuse LPA details" link for the test fixture lpa

    # Click reuse link
    When I click the "Reuse LPA details" link for the test fixture lpa
    Then I am taken to the type page for cloned lpa

    # Select Property and financial affairs as type
    When I choose Property and Finance
    And I click "save"
    Then I am taken to the donor page
    And I see "This LPA covers property and financial affairs" in the page text

    # Select donor + save
    When I click "add-donor"
    Then I can find "form-reuse-details"

    When I click the option labelled with "Test User" in the reuse popup
    And I click "continue"
    And I click "form-save"
    Then I cannot find "form-donor"
    And I see "Mr Test User" in the page text

    When I click "save-and-continue"
    Then I am taken to the when lpa starts page

    # Select when LPA can be used
    When I check "when-no-capacity"
    And I click "save"
    Then I am taken to the primary attorney page

    # Select primary attorney + save
    When I click "add-attorney"
    Then I can find "form-reuse-details"

    When I click the option labelled with "Nancy Garrison" in the reuse popup
    And I click "continue"
    And I click "form-save"
    Then I cannot find "form-reuse-details"
    And I see "Mrs Nancy Garrison" in the page text

    When I click "save"
    Then I am taken to the replacement attorney page

    # Skip replacement attorneys
    When I click "save"
    Then I am taken to the certificate provider page

    # Skip certificate provider
    When I click "skip-certificate-provider"
    Then I am taken to the people to notify page

    # Person to notify: save and continue
    When I click "save"
    Then I am taken to the instructions page

    # Preferences and instructions: save and continue
    When I click "save"
    Then I am taken to the applicant page

    # Select donor as applicant (person registering the LPA)
    When I check "whoIsRegistering-donor"
    And I click "save"
    Then I am taken to the correspondent page
    And I can find "change-correspondent" with data-inited

    # Correspondent: check correspondent from cloned LPA is visible, select them and save, check they are selected
    When I click "change-correspondent"
    Then I can find "form-reuse-details"
    And I can see "Nancy Garrison (was the correspondent)" as a label in the reuse popup

    When I click the option labelled with "Nancy Garrison (was the correspondent)" in the reuse popup

    # For some reason, this popup doesn't follow the same pattern as any of the others:
    # clicking "Continue" saves the selection, rather than showing the details inside an edit popup
    And I click "continue"
    Then I cannot find "form-reuse-details"
    And I see "Mrs Nancy Garrison" in the page text
