@PartOfStitchedRun
Feature: Add a Certificate Provider to a Health and Welfare LPA

    I want to add a Certificate Provider to a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with a donor, attorneys and replacement attorneys

    @focus @CleanupFixtures
    Scenario: Add Certificate Provider
        When I log in as appropriate test user
        And I visit the certificate provider page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        Then the page matches the "add-certificate-provider" baseline image
        When I click "add-certificate-provider"
        Then I can find "form-certificate-provider"
        And I can find "form-cancel"
        And I can find "name-title" with 8 options
        # todo - casper just looked for use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"
        When I select "Mr" on "name-title"
        And I force fill out
            | name-first | Reece |
            | name-last | Richards |
            | address-address1 | 11 Brookside |
            | address-address2 | Cholsey |
            | address-address3 | Wallingford, Oxfordshire |
            | address-postcode | OX10 9NN |
        Then the page matches the "add-certificate-provider-form" baseline image
        And I click "form-save"
        # check certificate provider is listed and save points to replacement attorney page
        Then I see "Mr Reece Richards" in the page text
        And I can find save pointing to people to notify page
        # Casper checked for existence of delete link, here we click it then cancel, which is more thorough
        When I click "delete-certificate-provider"
        And I click "cancel"
        And I click "view-change-certificate-provider"
        Then I can find "form-certificate-provider"
        And I see "name-title" prepopulated with "Mr"
        And I see form prepopulated with
            | name-first | Reece |
            | name-last | Richards |
            | address-address1 | 11 Brookside |
            | address-address2 | Cholsey |
            | address-address3 | Wallingford, Oxfordshire |
            | address-postcode | OX10 9NN |
        And I click "form-cancel"
        When I click "save"
        Then I am taken to the people to notify page
