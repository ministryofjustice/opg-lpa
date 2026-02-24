@PartOfStitchedRun
Feature: Add Replacement Attorneys to a Property and Finance LPA

    I want to add Replacement Attorneys to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with a donor and attorneys

    @focus @CleanupFixtures
    Scenario: Add Replacment Attorneys
        When I log in as appropriate test user
        And I visit the replacement attorney page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        # note that for the clone test, we do not add a replacement attorney, as that is already covered by PF test, the scenario we cover here is not having replacement attorney(s)
        Then the page matches the "add-replacement-attorney-pf-clone" baseline image
        When I click "add-replacement-attorney"
        And I can find use-my-details if lpa is new
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-attorney"
        And I can find "postcode-lookup"
        And I can find "name-title" with 8 options
        When I click "form-cancel"
        And I cannot find "form-attorney"
        Then the page matches the "add-replacement-attorney-pf-clone-form" baseline image
        And I click "save"
        Then I am taken to the certificate provider page
