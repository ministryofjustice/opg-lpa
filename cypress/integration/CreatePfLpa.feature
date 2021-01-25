Feature: Create a Property and Finance LPA

    I want to create a Property and Finance LPA

    Background:
        Given I ignore application exceptions
 
    #    @focus
    Scenario: Dashboard has Link to Type page
        # we use seeded user here because a newly signed-up user would not yet have a dashboard page
        Given I log in as seeded user
        When I click "createnewlpa"
        Then I am taken to the lpa type page
  
    #@focus
    Scenario: Create LPA with error first
        Given I log in as appropriate test user
        Then I visit the type page
        When I click "save"
        Then I see in the page text
            | There was a problem submitting the form |
            | You need to do the following: |
            | Choose a type of LPA |
        Then I choose Property and Finance
        When I click "save"
        Then I am taken to the donor page for property and finance
        And I see "Who is the donor for this LPA?" in the page text
        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor-details"
        Then I can see popup

    @focus
    Scenario: Create LPA normal path
        Given I log in as appropriate test user
        Then I visit the type page
        Then I choose Property and Finance
        When I click "save"
        Then I am taken to the donor page for property and finance
        And I see "Who is the donor for this LPA?" in the page text
        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor-details"
        Then I can see popup
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
        When I click "view-edit-details"
        Then I can see popup
        # TODO need to check title set to Mrs
        And I see form prefilled out with
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
        Then I click "form-cancel"
