@PartOfStitchedRun
Feature: Add a correspondent to a Property and Finance LPA

    I want to add a correspondent to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences, applicant

    @focus @CleanupFixtures
    Scenario: Create LPA normal path
        When I log in as appropriate test user
        And I visit the correspondent page for the test fixture lpa
        Then I am taken to the correspondent page
        # first test - do nothing and ensure the donor is correspondent as default (note that this will only be the case running tests locally with fixtures, whereas
        # for stitched clone run, the attorney has already been set as the correspondent on the who-is-registering page
        When I click "save"
        Then I am taken to the who are you page
        Then I see "The LPA will be sent to Mrs Nancy Garrison" in the page text

        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        # first test - do nothing and ensure the donor is correspondent as default
        When I click "save"
        Then I am taken to the who are you page
        Then I see "The LPA will be sent to Mrs Nancy Garrison" in the page text

        # second test , choose certificate provider as correspondent
        When I click the last occurrence of "accordion-view-change"
        Then I am taken to the correspondent page
        And I can find "change-correspondent" with data-inited
        When I click "change-correspondent"
        Then I can find "form-reuse-details"
        And I see "Which details would you like to reuse?" in the page text
        When I check "reuse-details-3"
        And I click "continue"
        And I see "Mr Reece Richards" in the page text
        And I see "11 Brookside, Cholsey, Wallingford, Oxfordshire, OX10 9NN" in the page text
        And I check "contactByPost" 
        When I click "save"
        Then I am taken to the who are you page
        And I see "The LPA will be sent to Mr Reece Richards" in the page text

        # third test - switch to attorney as correspondent
        When I click the last occurrence of "accordion-view-change"
        Then I am taken to the correspondent page
        And I can find "change-correspondent" with data-inited
        When I click "change-correspondent"
        Then I can find "form-reuse-details"
        When I check "reuse-details-2"
        And I click "continue"
        Then I can find "form-correspondent"
        And I click "form-save"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-correspondent"
        And I see "Standard Trust" in the page text
        And I see "1 Laburnum Place, Sketty, Swansea, Abertawe, SA2 8HT" in the page text
        When I click "save"
        Then I am taken to the who are you page
        And I see "The LPA will be sent to Standard Trust" in the page text
