@CreateLpa
Feature: Add People to Notify to a Property and Finance LPA

    I want to add People to Notify to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider

    @focus @CleanupFixtures
    Scenario: Add person to notify
        When I log in as appropriate test user
        And I visit the people to notify page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        When I click "add"
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-people-to-notify"
        And I can find "form-cancel"
        And I can find "name-title" with 8 options
        # todo - casper just looked for use-my-details. We need ultimately to actually test this
        And I can find use-my-details if lpa is new
        When I force fill out
            | name-first | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | name-last | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address1 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address2 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address3 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-postcode | BS18 6PL |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the title of the person to notify |
            | Enter a first name that's less than 51 characters long |
            | Enter a last name that's less than 51 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        When I select "Other" on "name-title"
        And I force fill out
            | name-title | Sir |
            | name-first | Anthony |
            | name-last | Webb |
            | address-address1 | Brickhill Cottage |
            | address-address2 | Birch Cross |
            | address-address3 | Marchington, Uttoxeter, Staffordshire |
        And I click "form-save"
        Then I see "Sir Anthony Webb" in the page text
        When I click "view-change"
        Then I can find "form-people-to-notify"
        And I see form prepopulated with
            | name-title | Sir |
            | name-first | Anthony |
            | name-last | Webb |
            | address-address1 | Brickhill Cottage |
            | address-address2 | Birch Cross |
            | address-address3 | Marchington, Uttoxeter, Staffordshire |
            | address-postcode | BS18 6PL |
        And I click "form-cancel"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-people-to-notify" 
        When I click "save"
        Then I am taken to the instructions page
