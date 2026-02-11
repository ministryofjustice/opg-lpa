@PartOfStitchedRun
Feature: Add attorneys to a Property and Finance LPA

    I want to add attorneys to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with a donor

    @focus @CleanupFixtures
    Scenario: Add Attorneys
        When I log in as appropriate test user
        And I visit the primary attorney page for the test fixture lpa

        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove
        Then the page matches the "add-attorney" baseline image
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
        Then the page matches the "add-attorney-form" baseline image
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
        # NB we don't need to type in all fields, just the name then move to another field
        # so the JS change event triggers to show the duplicate person error message
        And I force fill out
            | name-first | Amy |
            | name-last | Wheeler |
            | dob-date-day| 22 |
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
        Then the page matches the "delete-attorney" baseline image
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

        # LPAL-1033 - should not see this error message when name is empty,
        # only the "Enter the company's name" error message
        And I do not see "must-be-greater-than-or-equal:1" in the page text

        When I type " " into "number"
        And I force fill out
            | name | Standard Trust |
            | email-address| opglpademo+trustcorp@gmail.com |
            | address-address1 | 1 Laburnum Place |
            | address-address2 | Sketty |
            | address-address3 | Swansea, Abertawe |
            | address-postcode | SA2 8HT |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the company's registration number |

        # LPAL-1033 - should not see this error message when number is empty,
        # only the "Enter the company's registration number" error message
        And I do not see "must-be-greater-than-or-equal:1" in the page text

        When I type "qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kBPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB" into "name" working around cypress bug
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter a company name that's less than 76 characters long |

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
        Then I can find hidden "how-depends"
        # test save without selecting anything
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | How should the attorneys make decisions |
        And I see "Error" in the title
        When I click "how-depends"
        Then I can find "how-details" and it is visible
        # test save without typing anything in how-details
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Tell us which decisions have to be made jointly, and which can be made jointly and severally |
        And I see "Error" in the title
        When I click "how-jointly-attorney-severally"
        When I click "save"
        Then I am taken to the replacement attorney page
