@PartOfStitchedRun
Feature: Add donor to Property and Finance LPA

    I want to add a donor to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture
        And Ordnance Survey postcode lookup responses are stubbed out for good postcode B1 1TF

    @focus @CleanupFixtures
    Scenario: Add Donor to LPA
        When I log in as appropriate test user
        And I visit the donor page for the test fixture lpa
        Then I see "This LPA covers property and financial affairs" in the page text
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove
        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor"
        # todo - casper merely checked for existence of use-my-details. We need ultimately to actually test this
        Then I can find use-my-details if lpa is new
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-donor"
        And accessibility checks should pass for "donorPF page with popup open"
        # ensure we are on the donor form , in case re-use details form was previously shown
        When I type "B1 1TF" into "postcode-lookup" working around cypress bug
        # cypress is not reliable at filling in postcode fully before hitting next button, so, ensure it is now filled in
        And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
        And I click element marked "Find UK address"
        # casper simply checked for 6 options. PAF was updated to remove some of the addresses from the tested postcode so we check for 3. We may ultimately wish to check the values
        Then I can find "address-search-result" with 6 options
        # casper simply checked for 8 options so we do too, but we may ultimately wish to check the values
        And I can find "name-title" with 8 options
        When I force fill out
            | name-first | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | name-last | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | otherNames | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | dob-date-day | 22 |
            | dob-date-month | 10 |
            | dob-date-year | 1988 |
            | email-address | opglpademo+NancyGarrison@gmail.com |
            | address-address1 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address2 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address3 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-postcode | PO38 1UL |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the donor's title |
            | Enter a first name that's less than 54 characters long |
            | Enter a last name that's less than 62 characters long |
            | Enter other names that are less than 51 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        When I select "Mrs" on "name-title"
        And I force fill out
            | name-first | Nancy |
            | name-last | Garrison |
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1988 |
            | email-address| opglpademo+NancyGarrison@gmail.com |
            | address-address1| Bank End Farm House |
            | address-address2| Undercliff Drive |
            | address-address3| Ventnor, Isle of Wight |
            | address-postcode| PO38 1UL |
        And I clear the value in "otherNames"
        And I check "cannot-sign"
        And I click "form-save"
        Then I cannot find "form-donor"
        Then I can find "save-and-continue"
        And I cannot find "add-donor"
        And I see "Mrs Nancy Garrison" in the page text
        When I click "view-change-donor"
        Then I can find "form-donor"
        And I see "name-title" prepopulated with "Mrs"
        And I see form prepopulated with
            | name-first | Nancy |
            | name-last | Garrison |
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1988 |
            | email-address| opglpademo+NancyGarrison@gmail.com |
            | address-address1| Bank End Farm House |
            | address-address2| Undercliff Drive |
            | address-address3| Ventnor, Isle of Wight |
            | address-postcode| PO38 1UL |
        And "cannot-sign" is checked
        When I click "form-cancel"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-donor"
        When I click "save-and-continue"
        And I am taken to the when lpa starts page
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose when your LPA can be used |
        When I check "when-no-capacity"
        Then the page matches the "when-lpa-starts" baseline image
        And I click "save"
        Then I am taken to the primary attorney page
        And I see "The LPA starts only if the donor does not have mental capacity" in the page text
        And I do not see "The LPA starts as soon as it's registered (with the donor's consent)" in the page text
        And I cannot find "save"
        When I click occurrence 1 of "accordion-view-change"
        Then I am taken to the when lpa starts page
        When I check "when-now"
        And I click "save"
        Then I am taken to the primary attorney page
        And I see "The LPA starts as soon as it's registered (with the donor's consent)" in the page text
        And I do not see "The LPA starts only if the donor does not have mental capacity" in the page text
