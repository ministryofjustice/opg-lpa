@StitchedPF
Feature: Property and Finance LPA starting from the Type page

    I want to go to the type page and create a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I log in as appropriate test user
        And If I am on dashboard I visit the type page

    @focus @CleanupFixtures
    Scenario: Create LPA with error first
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose a type of LPA |
        And I see "Error" in the title
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text
        When I click "add-donor"
        Then I can find "form-donor" 

    @focus @CleanupFixtures
    Scenario: Choose Property and Finance as Lpa Type
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text
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
        # casper simply checked for 6 options so we do too, but we may ultimately wish to check the values
        Then I can find "address-search-result" with 6 options
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
        Then I cannot find "form-donor" 
        Then I can find "save-and-continue"
        And I cannot find "add-donor"
        And I see "Mrs Nancy Garrison" in the page text
        # following line uses force click because view-change-donor button is partly obscured
        When I force click "view-change-donor"
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
        When I click "add-attorney"
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-attorney"
        And I can find "form-cancel"
        And I can find "postcode-lookup"
        And I can find "name-title" with 8 options
        # todo - casper merely checked for existence of use-my-details. We need ultimately to actually test this
        And I can find use-my-details if lpa is new
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
        When I click occurrence 2 of "accordion-view-change"
        Then I am taken to the primary attorney page
        # Test adding same attorney twice
        When I click "add-attorney"
        And I opt not to re-use details if lpa is a clone
        # line below is deliberately Mr rather than Mrs, as was done in Casper tests
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
        When I click occurrence 1 of "delete-attorney"
        And I click "delete"
        # Check we are back to 1 attorney listed and save points back to replacement attorney page
        Then I am taken to the primary attorney page
        And I see "Mrs Amy Wheeler" in the page text
        And I do not see "Mr David Wheeler" in the page text
        And I can find save pointing to replacement attorney page
        # Re-add 2cnd attorney, first with errors
        When I click "add-attorney"
        And I opt not to re-use details if lpa is a clone
        And I click "use-trust-corporation"
        And I type " " into "name"
        And I force fill out
            | number | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kBPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | email-address| opglpademo+trustcorp@gmail.com |
            | address-address1 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address2 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address3 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-postcode | SA2 8HT |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the company's name |
            | Enter a registration number that's less than 76 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        When I type "qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kBPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB" into "name"
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
        And I see "Standard Trust" in the page text
        # re-view 1st attorney
        When I click occurrence 0 of "view-change-attorney"
        Then I can find "form-attorney"
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
        When I click "save"
        Then I am taken to the primary attorney decisions page
        # test save without selecting anything
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | How should the attorneys make decisions |
        And I see "Error" in the title
        When I click "how-depends"
        # test save without typing anything in how-details
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Tell us which decisions have to be made jointly, and which can be made jointly and severally |
        And I see "Error" in the title
        When I click "how-jointly-attorney-severally"
        When I click "save"
        Then I am taken to the replacement attorney page
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

        When I click "save"
        Then I am taken to the certificate provider page
        When I click occurrence 4 of "accordion-view-change"
        Then I am taken to the replacement attorney page
        When I click "add-replacement-attorney"
        And I can find use-my-details if lpa is new
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-attorney"
        And I can find "postcode-lookup"
        And I can find "name-title" with 8 options
        When I force fill out
            | name-first | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | name-last | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | dob-date-day | 01 |
            | dob-date-month | 02 |
            | dob-date-year | 1937 |
            | address-address1 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address2 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address3 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-postcode | TA3 7HF |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the replacement attorney's title |
            | Enter a first name that's less than 51 characters long |
            | Enter a last name that's less than 51 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        When I select "Ms" on "name-title"
        And I force fill out
            | name-first | Isobel |
            | name-last | Ward |
            | address-address1 | 2 Westview |
            | address-address2 | Staplehay |
            | address-address3 | Trull, Taunton, Somerset |
        And I click "form-save"
        Then I cannot find "form-attorney" 
        Then I see "Ms Isobel Ward" in the page text
        # Test adding same attorney twice
        When I click "add-replacement-attorney"
        And I can find use-my-details if lpa is new
        And I opt not to re-use details if lpa is a clone
        # deliberately Mrs instead of Ms this time
        When I select "Mrs" on "name-title"
        And I force fill out
            | name-first | Isobel |
            | name-last | Ward |
            | dob-date-day | 22 |
            | dob-date-month | 10 |
            | dob-date-year | 1988 |
            | address-address1| Brickhill Cottage |
            | address-address2| Birch Cross |
            | address-address3| Marchington, Uttoxeter, Staffordshire |
            | address-postcode| ST14 8NX |
        Then I see "There is also a replacement attorney called Isobel Ward. A person cannot be named as a replacement attorney twice on the same LPA." in the page text
        When I select "Mr" on "name-title"
        And I force fill out
            | name-first | Ewan |
            | name-last | Adams |
            | dob-date-day | 12 |
            | dob-date-month | 03 |
            | dob-date-year | 1972 |
            | address-address1 | 2 Westview |
            | address-address2 | Staplehay |
            | address-address3 | Trull, Taunton, Somerset |
            | address-postcode | TA3 7HF |
        When I click "form-save"
        Then I cannot find "form-attorney" 
        Then I see "Ms Isobel Ward" in the page text
        And I see "Mr Ewan Adams" in the page text
        When I click occurrence 1 of "delete-attorney"
        And I click "delete"
        # Check we are back to 1 attorney listed
        Then I am taken to the replacement attorney page
        Then I do not see "Mr Ewan Adams" in the page text
        # re-add 2cnd replacement attorney
        When I click "add-replacement-attorney"
        And I can find use-my-details if lpa is new
        And I opt not to re-use details if lpa is a clone
        And I select "Mr" on "name-title"
        And I force fill out
            | name-first | Ewan |
            | name-last | Adams |
            | dob-date-day | 12 |
            | dob-date-month | 03 |
            | dob-date-year | 1972 |
            | address-address1 | 2 Westview |
            | address-address2 | Staplehay |
            | address-address3 | Trull, Taunton, Somerset |
            | address-postcode | TA3 7HF |
        When I click "form-save"
        Then I cannot find "form-attorney" 
        # both replacement attorneys can be seen again
        Then I see "Ms Isobel Ward" in the page text
        And I see "Mr Ewan Adams" in the page text
        # re-view 2cnd replacement attorney
        When I click occurrence 1 of "view-change-attorney"
        Then I can find "form-attorney"
        And I see "name-title" prepopulated with "Mr"
        And I see form prepopulated with
            | name-first | Ewan |
            | name-last | Adams |
            | dob-date-day | 12 |
            | dob-date-month | 03 |
            | dob-date-year | 1972 |
            | address-address1 | 2 Westview |
            | address-address2 | Staplehay |
            | address-address3 | Trull, Taunton, Somerset |
            | address-postcode | TA3 7HF |
        When I click "form-cancel"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-attorney" 
        When I click "save"
        Then I am taken to the when replacement attorneys step in page

        # Replacement Attorney details tests end and when Replacement Attorney should Act tests start. 

        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose how the replacement attorneys should step in |
        And I see "Error" in the title
        When I click "when-depends"
        And I click "save"
        Then I see in the page text
            | There is a problem |
            | Tell us how you'd like the replacement attorneys to step in |
        And I see "Error" in the title
        And I can find "when-details" wrapped with error highlighting
        When I click "when-first"
        And I click "save"
        Then I am taken to the certificate provider page
        When I click occurrence 5 of "accordion-view-change"
        Then I am taken to the when replacement attorneys step in page
        When I click "when-last"
        And I click "save"
        Then I am taken to the how replacement attorneys make decision page

        # When Replacement Attorney should Act tests end and How Replacement Attorney make decisions start. 

        # test save without selecting anything
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | How should the replacement attorneys make decisions |
        And I see "Error" in the title
        When I click "how-depends"
        # test save without typing anything in how-details
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Tell us which decisions have to be made jointly, and which can be made jointly and severally |
        And I see "Error" in the title
        When I click "how-jointly-attorney-severally"
        When I click "save"
        Then I am taken to the certificate provider page

        When I click "add-certificate-provider"
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-certificate-provider"
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
            | address-postcode | OX10 9NN |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the certificate provider's title |
            | Enter a first name that's less than 51 characters long |
            | Enter a last name that's less than 51 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        When I select "Mr" on "name-title"
        And I force fill out
            | name-first | Reece |
            | name-last | Richards |
            | address-address1 | 11 Brookside |
            | address-address2 | Cholsey |
            | address-address3 | Wallingford, Oxfordshire |
        And I click "form-save"
        # check certificate provider is listed and save points to people to notify page
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

        Then I can find "instruction" but it is not visible
        And I can find "preferences" but it is not visible
        When I click "add-extra-preferences"
        Then I can find "instruction" and it is visible
        And I can find "preferences" and it is visible
        And I fill out
            | instruction | Lorem Ipsum |
            | preferences | Neque porro quisquam |
        When I click "save"
        Then I am taken to the applicant page
        When I click the last occurrence of "accordion-view-change"
        Then I see in the page text
            | Lorem Ipsum |
            | Neque porro quisquam |
        When I click "save"
        Then I am taken to the applicant page
        When I visit link containing "preview the LPA"


        Then I am taken to the summary page
        And I see the following summary information
            | Type | Property and finance | |
            | Donor | | |
            | Name | Mrs Nancy Garrison | donor |
            | Date of birth | 22 October 1988 | |
            | Email address | opglpademo+NancyGarrison@gmail.com | |
            | Address | Bank End Farm House $ Undercliff Drive$ Ventnor, Isle of Wight $ PO38 1UL | |
            | The donor can physically sign or make a mark on the LPA | No | |
            | When LPA starts |  As soon as it's registered (and with the donor's consent) | when-lpa-starts |
            | 1st attorney | | |
            | Name | Mrs Amy Wheeler | primary-attorney |
            | Date of birth | 22 October 1988 | |
            | Email address | opglpademo+AmyWheeler@gmail.com | |
            | Address | Brickhill Cottage $ Birch Cross $ Marchington, Uttoxeter, Staffordshire $ ST14 8NX | |
            | 2nd attorney | | |
            | Name | Standard Trust | primary-attorney |
            | Company number | 678437685 | |
            | Email address | opglpademo+trustcorp@gmail.com | |
            | Address | 1 Laburnum Place $ Sketty $ Swansea, Abertawe $ SA2 8HT | |
            | Attorney decisions | | |
            | How decisions are made | The attorneys will act jointly and severally | how-primary-attorneys-make-decision |
            | 1st replacement attorney | | |
            | Name | Ms Isobel Ward | replacement-attorney |
            | Date of birth | 1 February 1937 | |
            | Address | 2 Westview $ Staplehay $ Trull, Taunton, Somerset $ TA3 7HF | |
            | 2nd replacement attorney | | |
            | Name | Mr Ewan Adams | replacement-attorney |
            | Date of birth | 12 March 1972 | |
            | Address | 2 Westview $ Staplehay $ Trull, Taunton, Somerset $ TA3 7HF | |
            | Replacement attorney decisions | | |
            | When they step in | The replacement attorneys will only step in when none of the original attorneys can act | when-replacement-attorney-step-in |
            | How decisions are made | The replacement attorneys will act jointly and severally | how-replacement-attorneys-make-decision |
            | Certificate provider | | |
            | Name | Mr Reece Richards | certificate-provider |
            | Address | 11 Brookside $ Cholsey $ Wallingford, Oxfordshire $ OX10 9NN | |
            | Person to notify | | |
            | Name | Sir Anthony Webb | people-to-notify |
            | Address | Brickhill Cottage $ Birch Cross $ Marchington, Uttoxeter, Staffordshire $ BS18 6PL | |
        And I can find draft download link
        When I click "continue"
        Then I am taken to the applicant page

        Then I am taken to the applicant page
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Select the person who is applying to register the LPA |
        And I see "Error" in the title
        # select the donor as applicant
        When I check "whoIsRegistering-donor"
        And I click "save"
        Then I am taken to the correspondent page

        And I can find "contactInWelsh-0"
        And I can find "contactInWelsh-1"
        And I can find "contactByPost"
        And I can find "contactByPhone"
        And I can find hidden "phone-number"
        And I can find hidden "email-address"
        And I can find "change-correspondent" with data-inited
        When I click "change-correspondent"
        Then I can find "form-reuse-details"
        And I see "Which details would you like to reuse?" in the page text
        # donor is correspondent as default
        And I see "Mrs Nancy Garrison" in the page text
        And "contactByEmail" is checked
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

        And I can find "who"
        And I can find "who-friend-or-family"
        And I can find "who-finance-professional"
        And I can find "who-legal-professional"
        And I can find "who-estate-planning-professional"
        And I can find "who-digital-partner"
        And I can find "who-charity"
        And I can find "who-organisation"
        And I can find "who-other"
        And I can find "who-notSaid"
        When I click "save"
        Then I see "There is a problem" in the page text
        When I check "who"
        And I click "save"
        Then I am taken to the repeat application page
        When I click the last occurrence of "accordion-view-change"
        Then I am taken to the who are you page
        And I see "Thanks, you have already answered this question" in the page text
        When I click "continue"
        Then I am taken to the repeat application page

        # repeatCaseNumber should be hidden initially
        And I can find hidden "repeatCaseNumber"

        When I check "isRepeatApplication-is-repeat"
        And I click "save"
        When I click element marked "Confirm and continue"
        Then I see in the page text
            | There is a problem |
            | If you are making a repeat application, you need to enter the case number given to you by the Office of the Public Guardian. | 
        # test more than 12 digits in case number
        When I type "12345678910121213" into "repeatCaseNumber"
        And I click "save"
        When I click element marked "Confirm and continue"
        Then I see in the page text
            | There is a problem |
            | Case Number must be twelve digits |
        # test less than 12 digits in case number
        When I clear the value in "repeatCaseNumber"
        And I type "1234" into "repeatCaseNumber"
        And I click "save"
        When I click element marked "Confirm and continue"
        Then I see in the page text
            | There is a problem |
            | Case Number must be twelve digits |
       # for PF we test typing in a case number. The other scenario where this is not a repeat, is covered in HW feature
        When I clear the value in "repeatCaseNumber"
        And I type "123456789012" into "repeatCaseNumber"
        And I click "save"
        Then I can see popup
        When I click element marked "Confirm and continue"
        Then I am taken to the fee reduction page

        And I can find "reducedFeeReceivesBenefits"
        And I can find "reducedFeeUniversalCredit"
        And I can find "reducedFeeLowIncome"
        And I can find "notApply"

        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Select if the donor does or does not want to apply for a fee reduction |

        When I check "reducedFeeReceivesBenefits"
        Then I see "To apply to pay no fee, you must send us a ‘fee remissions and exemptions form’ and copies of letters from the Department for Work and Pensions (DWP) or the benefit provider as proof that the donor is receiving benefits." in the page text
        And I should not see "Because Universal Credit is in its trial phase and replaces several existing benefits, we're looking at fee reductions on a case-by-case basis." in the page text
        And I should not see "The documents must have the donor’s title, full name, address and postcode printed on them and they must be from the current tax year. Tax years run from 6 April one year to 5 April the next year." in the page text

        When I check "reducedFeeUniversalCredit"
        Then I see "Because Universal Credit is in its trial phase and replaces several existing benefits, we're looking at fee reductions on a case-by-case basis." in the page text
        And I should not see "To apply to pay no fee, you must send us a ‘fee remissions and exemptions form’ and copies of letters from the Department for Work and Pensions (DWP) or the benefit provider as proof that the donor is receiving benefits." in the page text
        And I should not see "The documents must have the donor’s title, full name, address and postcode printed on them and they must be from the current tax year. Tax years run from 6 April one year to 5 April the next year." in the page text

        When I check "reducedFeeLowIncome"
        Then I see "The documents must have the donor’s title, full name, address and postcode printed on them and they must be from the current tax year. Tax years run from 6 April one year to 5 April the next year." in the page text
        And I should not see "Because Universal Credit is in its trial phase and replaces several existing benefits, we're looking at fee reductions on a case-by-case basis." in the page text
        And I should not see "To apply to pay no fee, you must send us a ‘fee remissions and exemptions form’ and copies of letters from the Department for Work and Pensions (DWP) or the benefit provider as proof that the donor is receiving benefits." in the page text

        When I check "notApply"
        When I click "save"
        Then I am taken to the checkout page
        And I see "Application fee: £41 as you are not claiming a reduction" in the page text

        When I click the last occurrence of "cya-change"
        When I check "reducedFeeLowIncome"
        And I click "save"
        Then I am taken to the checkout page
        And I see "Application fee: £20.50 as the donor has an income of less than £12,000" in the page text

        And I see the following summary information
            | Type | Property and finance | |
            | Donor | | |
            | Name | Mrs Nancy Garrison | donor |
            | Date of birth | 22 October 1988 | |
            | Email address | opglpademo+NancyGarrison@gmail.com | |
            | Address | Bank End Farm House $ Undercliff Drive$ Ventnor, Isle of Wight $ PO38 1UL | |
            | The donor can physically sign or make a mark on the LPA | No | |
            | When LPA starts |  As soon as it's registered (and with the donor's consent) | when-lpa-starts |
            | 1st attorney | | |
            | Name | Mrs Amy Wheeler | primary-attorney |
            | Date of birth | 22 October 1988 | |
            | Email address | opglpademo+AmyWheeler@gmail.com | |
            | Address | Brickhill Cottage $ Birch Cross $ Marchington, Uttoxeter, Staffordshire $ ST14 8NX | |
            | 2nd attorney | | |
            | Name | Standard Trust | primary-attorney |
            | Company number | 678437685 | |
            | Email address | opglpademo+trustcorp@gmail.com | |
            | Address | 1 Laburnum Place $ Sketty $ Swansea, Abertawe $ SA2 8HT | |
            | Attorney decisions | | |
            | How decisions are made | The attorneys will act jointly and severally | how-primary-attorneys-make-decision |
            | 1st replacement attorney | | |
            | Name | Ms Isobel Ward | replacement-attorney |
            | Date of birth | 1 February 1937 | |
            | Address | 2 Westview $ Staplehay $ Trull, Taunton, Somerset $ TA3 7HF | |
            | 2nd replacement attorney | | |
            | Name | Mr Ewan Adams | replacement-attorney |
            | Date of birth | 12 March 1972 | |
            | Address | 2 Westview $ Staplehay $ Trull, Taunton, Somerset $ TA3 7HF | |
            | Replacement attorney decisions | | |
            | When they step in | The replacement attorneys will only step in when none of the original attorneys can act | when-replacement-attorney-step-in |
            | How decisions are made | The replacement attorneys will act jointly and severally | how-replacement-attorneys-make-decision |
            | Certificate provider | | |
            | Name | Mr Reece Richards | certificate-provider |
            | Address | 11 Brookside $ Cholsey $ Wallingford, Oxfordshire $ OX10 9NN | |
            | Person to notify | | |
            | Name | Sir Anthony Webb | people-to-notify |
            | Address | Brickhill Cottage $ Birch Cross $ Marchington, Uttoxeter, Staffordshire $ BS18 6PL | |
            | Preferences | Neque porro quisquam | instructions |
            | Instructions | Lorem Ipsum | instructions |
            | Who is registering the LPA | Donor | applicant |
            | Correspondent | | |
            | Name | Mrs Nancy Garrison | correspondent |
            | Email address | opglpademo+NancyGarrison@gmail.com | |
            | Address | Bank End Farm House $ Undercliff Drive$ Ventnor, Isle of Wight $ PO38 1UL | |
            | Repeat application | This is a repeat application with case number 12345678 | repeat-application |
            | Application fee | Application fee: £20.50 as the donor has an income of less than £12,000 | fee-reduction |
        And I can find "confirm-and-pay-by-card"
        And I can find "confirm-and-pay-by-cheque"

        When I click "confirm-and-pay-by-cheque"
        Then I am taken to the complete page
        And I can find link pointing to "/lp1"
        # /lp3 link is for person  to notify
        And I can find link pointing to "/lp3"
        # note that /lpa120 link only appears when fee reduction is requested
        And I can find link pointing to "/lpa120"
        # lines below will be uncommented once we fix issues with pdf generation unreliability
        #And I can get pdf from link containing "Download your print-ready LPA form"
        #And I can get pdf from link containing "Download the letter to send"
