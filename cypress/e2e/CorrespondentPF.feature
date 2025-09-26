@PartOfStitchedRun
Feature: Add a correspondent to a Property and Finance LPA

    I want to add a correspondent to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant

    @focus @CleanupFixtures
    Scenario: Create LPA normal path
        When I log in as appropriate test user
        And I visit the correspondent page for the test fixture lpa
        Then I am taken to the correspondent page
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        And I can find "contactInWelsh-0"
        And I can find "contactInWelsh-1"
        And I can find "contactByPost"
        And I can find "contactByPhone"
        And I can find hidden "phone-number"
        And I can find "change-correspondent" with data-inited
        When I click "change-correspondent"
        Then I can find "form-reuse-details"
        And I see "Which details would you like to reuse?" in the page text
        # donor is correspondent as default
        And I see "Mrs Nancy Garrison" in the page text
        And "contactByEmail" is checked
        And I can find "email-address" and it is visible
        # choose new correspondent
        When I opt not to re-use details
        Then I can find "form-correspondent"
        Then I see "Correspondent details" in the page text
        And I can find "postcode-lookup"
        And I can find "name-title" with 8 options
        And I force fill out
            | name-first | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | name-last | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | company-name | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address1| qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address2| qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address3| qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-postcode| BS18 6PL |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the correspondent's title |
            | Enter a first name that's less than 54 characters long |
            | Enter a last name that's less than 62 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        # force click needed on line below as sometimes button obscured
        When I force click "form-back"
        # we are taken back to re-use details page
        Then I can find "form-reuse-details"
        And I see "Which details would you like to reuse?" in the page text
        # choose donor as correspondent
        When I check "reuse-details-1"
        And I click "continue"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-reuse-details"
        And I see "Mrs Nancy Garrison" in the page text
        When I uncheck "contactByEmail"
        And I click "save"
        Then I see in the page text
            | There is a problem |
            | Select how the correspondent would like to be contacted |
        When I check "contactByEmail"
        And I click "save"
        Then I am taken to the who are you page
