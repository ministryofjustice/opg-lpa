Feature: Create a Property and Finance LPA

    I want to create a Property and Finance LPA

    Background:
        Given I ignore application exceptions
 
    #@focus
    Scenario: Create LPA with error first
        Given I log in as appropriate test user
        When If I am on dashboard I click to create lpa
        Then I am taken to the lpa type page
        When I click "save"
        Then I see in the page text
            | There was a problem submitting the form |
            | You need to do the following: |
            | Choose a type of LPA |
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page for property and finance
        And I see "Who is the donor for this LPA?" in the page text
        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup

    @focus
    Scenario: Create LPA normal path
        Given I log in as appropriate test user
        When If I am on dashboard I click to create lpa
        Then I am taken to the lpa type page
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page for property and finance
        And I see "Who is the donor for this LPA?" in the page text
        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup
        # todo - casper just looked for use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"
        #When I type "B1 1TF" into old style id "input#postcode-lookup"
        #And I click element marked "Find UK address"
        #Then I can find old style id "#address-search-result" with 6 options
        And I can find old style id "#name-title" with 8 options
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
            | There was a problem submitting the form |
            | Enter the donor's title |
            | Enter a first name that's less than 54 characters long |
            | Enter a last name that's less than 62 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        When I select "Mrs" on old style id "#name-title"
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
        And I see old style id "#name-title" prepopulated with "Mrs"
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
            | There was a problem submitting the form |
            | You need to do the following: |
            | Choose when your LPA can be used |
        When I check old style id "#when-now"
        And I click "save"
        Then I am taken to the primary attorney page

