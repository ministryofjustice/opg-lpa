Feature: Create a Property and Finance LPA

    I want to create a Property and Finance LPA

    # background steps logs us in and takes us to the type page
    Background:
        Given I ignore application exceptions
        When I log in as appropriate test user
        And If I am on dashboard I click to create lpa
        Then I am taken to the lpa type page

    @focus
    Scenario: Create LPA with error first
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose a type of LPA |
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text
        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup

    @focus
    Scenario: Create LPA normal path
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text
        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup
        # todo - casper merely checked for existence of use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"
        When I type "B1 1TF" into "postcode-lookup" working around cypress bug
        # cypress is not reliable at filling in postcode fully before hitting next button, so, ensure it is now filled in
        And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
        And I click element marked "Find UK address"
        # casper simply checked for 6 options so we do too, but we may ultimately wish to check the values
        Then I can find old style id "#address-search-result" with 6 options
        # casper simply checked for 8 options so we do too, but we may ultimately wish to check the values
        And I can find "name-title" with 8 options
        When I force fill out
            | name-first | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | name-last | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
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
        And I check "can-sign"
        And I click "form-save"
        Then I can find "save-and-continue"
        And I cannot find "add-donor"
        And I see "Mrs Nancy Garrison" in the page text
        # following line uses force click because view-change-donor button is partly obscured
        When I force click "view-change-donor"
        Then I can see popup
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
        When I click "form-cancel"
        And I click "save-and-continue"
        Then I am taken to the when lpa starts page
        And I see "When can the LPA be used?" in the page text
        # in this test we check the when-no-capacity exists, then a few lines down we actually click when-now
        And I can find old style id "#when-no-capacity"
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose when your LPA can be used |
        When I check "when-now"
        And I click "save"
        Then I am taken to the primary attorney page
        And I cannot find "save"
        When I click "add-attorney"
        Then I can see popup
        And I can find "form-cancel"
        And I can find "name-title" with 8 options
        # todo - casper merely checked for existence of use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"
        And I can find "use-trust-corporation"
        And I force fill out
            | name-first | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | name-last | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1988 |
            | email-address| opglpademo+AmyWheeler@gmail.com |
            | address-address1| qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address2| qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address3| qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-postcode| ST14 8NX |
        When I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the attorney's title |
            | Enter a first name that's less than 51 characters long |
            | Enter a last name that's less than 51 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        When I select "Mrs" on "name-title"
        And I force fill out
            | name-first | Amy |
            | name-last | Wheeler |
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1988 |
            | email-address| opglpademo+AmyWheeler@gmail.com |
            | address-address1| Brickhill Cottage |
            | address-address2| Birch Cross |
            | address-address3| Marchington, Uttoxeter, Staffordshire |
            | address-postcode| ST14 8NX |
        And I click "form-save"
        # check attorney is listed and save points to replacement attorney page
        Then I see "Mrs Amy Wheeler" in the page text
        And I can find save pointing to replacement attorney page
        # Casper checked for existence of delete link, here we click it then cancel, which is more thorough
        When I click "delete-attorney"
        And I click "cancel"
        When I click "save"
        Then I am taken to the replacement attorney page
        When I click third occurrence of "accordion-view-change"
        Then I am taken to the primary attorney page
        # Test adding same attorney twice
        When I click "add-attorney"
        # lie below is deliberately Mr rather than Mrs, as was done in Casper tests
        When I select "Mr" on "name-title"
        And I force fill out
            | name-first | Amy |
            | name-last | Wheeler |
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1988 |
            | email-address| opglpademo+AmyWheeler@gmail.com |
            | address-address1| Brickhill Cottage |
            | address-address2| Birch Cross |
            | address-address3| Marchington, Uttoxeter, Staffordshire |
            | address-postcode| ST14 8NX |
        Then I see "There is also an attorney called Amy Wheeler. A person cannot be named as an attorney twice on the same LPA." in the page text
        # Add 2cnd attorney
        When I select "Mr" on "name-title"
        And I force fill out
            | name-first | David |
            | name-last | Wheeler |
            | dob-date-day| 12 |
            | dob-date-month| 03 |
            | dob-date-year| 1972 |
            | email-address| opglpademo+DavidWheeler@gmail.com |
            | address-address1| Brickhill Cottage |
            | address-address2| Birch Cross |
            | address-address3| Marchington, Uttoxeter, Staffordshire |
            | address-postcode| ST14 8NX |
        And I click "form-save"
        # Check we can see the 2 attorneys listed and save now points to primary attorney decisions page
        Then I see "Mrs Amy Wheeler" in the page text
        And I see "Mr David Wheeler" in the page text
        And I can find save pointing to primary attorney decisions page
        # Delete 2cnd attorney
        When I click second occurrence of "delete-attorney"
        And I click "delete"
        # Check we are back to 1 attorney listed and save points back to replacement attorney page
        Then I am taken to the primary attorney page
        And I see "Mrs Amy Wheeler" in the page text
        And I do not see "Mr David Wheeler" in the page text
        And I can find save pointing to replacement attorney page
        # Re-add 2cnd attorney, first with errors
        When I click "add-attorney"
        And I click "use-trust-corporation"
        And I force fill out
            | name | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kBPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | number | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kBPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | email-address| opglpademo+trustcorp@gmail.com |
            | address-address1 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address2 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address3 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-postcode | SA2 8HT |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter a company name that's less than 76 characters long |
            | Enter a registration number that's less than 76 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        # Re-add 2cnd attorney, correctly this time
        When I force fill out
            | name | Standard Trust |
            | number | 678437685 |
            | email-address| opglpademo+trustcorp@gmail.com |
            | address-address1 | 1 Laburnum Place |
            | address-address2 | Sketty |
            | address-address3 | Swansea, Abertawe |
            | address-postcode | SA2 8HT |
        And I click "form-save"
        Then I can find save pointing to primary attorney decisions page
        # check we can see the 2 attorneys listed
        And I see "Mrs Amy Wheeler" in the page text
        And I see "Mr David Wheeler" in the page text
        And I see "Standard Trust" in the page text
        # re-view 1st attorney
        When I click first occurrence of "view-change-attorney"
        Then I can see popup
        And I see "name-title" prepopulated with "Mrs"
        And I see form prepopulated with
            | name-first | Amy |
            | name-last | Wheeler |
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1988 |
            | email-address| opglpademo+AmyWheeler@gmail.com |
            | address-address1| Brickhill Cottage |
            | address-address2| Birch Cross |
            | address-address3| Marchington, Uttoxeter, Staffordshire |
            | address-postcode| ST14 8NX |
        When I click "form-cancel"
        Then I am taken to the primary attorney page

    @focus
    Scenario: Fail to select type of LPA to create, error links to first radio (LPAL-248)
        Given I click "save"
        When I see in the page text
            | There is a problem |
            | Choose a type of LPA |
        And I visit link containing "Choose a type of LPA"
        Then I am focused on "type-property-and-financial"
