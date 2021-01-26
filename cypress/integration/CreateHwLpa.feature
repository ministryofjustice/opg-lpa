Feature: Create a Health and Welfare LPA

    I want to create a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
 
    #@focus
    Scenario: Create LPA with error first
        Given I log in as appropriate test user
        Then If I am on dashboard I click to create lpa
        And I am taken to the lpa type page
        When I click "save"
        Then I see in the page text
            | There was a problem submitting the form |
            | You need to do the following: |
            | Choose a type of LPA |
        Then I choose Health and Welfare
        When I click "save"
        Then I am taken to the donor page for health and welfare
        And I see "Who is the donor for this LPA?" in the page text
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup

    @focus
    Scenario: Create LPA normal path
        Given I log in as appropriate test user
        Then If I am on dashboard I click to create lpa
        And I am taken to the lpa type page
        Then I choose Health and Welfare
        When I click "save"
        Then I am taken to the donor page for health and welfare
        And I see "Who is the donor for this LPA?" in the page text
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup
        # casper simply checked for 8 options so we do too, but we may ultimately wish to check the values
        And I can find old style id "#name-title" with 8 options
        When I type "B1 1TF" into old style id "input#postcode-lookup"
        And I click element marked "Find UK address"
        # casper simply checked for 6 options so we do too, but we may ultimately wish to check the values
        Then I can find old style id "#address-search-result" with 6 options
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
