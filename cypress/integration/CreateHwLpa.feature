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
        When I choose Health and Welfare
        And I click "save"
        Then I am taken to the donor page 
        And I see "This LPA covers health and welfare" in the page text
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup

    @focus
    Scenario: Create LPA normal path
        Given I log in as appropriate test user
        Then If I am on dashboard I click to create lpa
        And I am taken to the lpa type page
        When I choose Health and Welfare
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers health and welfare" in the page text
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup
        # todo - casper just looked for use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"
        # casper simply checked for 8 options so we do too, but we may ultimately wish to check the values
        And I can find "name-title" with 8 options
        When I type "B1 1TF" into "postcode-lookup"
        And I click element marked "Find UK address"
        # casper simply checked for 6 options so we do too, but we may ultimately wish to check the values
        Then I can find old style id "#address-search-result" with 6 options
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
        Then I am taken to the life sustaining page
        And I see "Who does the donor want to make decisions about life-sustaining treatment?" in the page text
        # in this test we check CanSustainLife-0 (no option) exists, then a few lines down we actually click canSustainLife-1 (yes)
        And I can find old style id "#canSustainLife-0"
        When I click "save"
        Then I see in the page text
            | There was a problem submitting the form |
            | You need to do the following: |
            | Choose an option |
        When I check old style id "#canSustainLife-1"
        And I click "save"
        Then I am taken to the primary attorney page
