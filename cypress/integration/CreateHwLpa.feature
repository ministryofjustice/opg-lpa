Feature: Create a Health and Welfare LPA

    I want to create a Health and Welfare LPA

    # background steps logs us in and takes us to the type page
    Background:
        Given I ignore application exceptions
        When I log in as appropriate test user
        And If I am on dashboard I click to create lpa
        Then I am taken to the lpa type page

    #@focus
    Scenario: Create LPA with error first
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose a type of LPA |
        When I choose Health and Welfare
        And I click "save"
        Then I am taken to the donor page 
        And I see "This LPA covers health and welfare" in the page text
        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup

    @focus
    Scenario: Create LPA normal path
        When I choose Health and Welfare
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers health and welfare" in the page text
        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor"
        Then I can see popup
        # todo - casper merely checked for existence of use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"
        # casper simply checked for 8 options so we do too, but we may ultimately wish to check the values
        And I can find "name-title" with 8 options
        When I type "B1 1TF" into "postcode-lookup" working around cypress bug
        # cypress is not reliable at filling in postcode fully before hitting next button, so, ensure it is now filled in
        And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
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
            | There is a problem |
            | Choose an option |
        When I check old style id "#canSustainLife-1"
        And I click "save"
        Then I am taken to the primary attorney page
        And I cannot find "save"

        # Donor page tests end here and Primary Attorney page tests start. Ultimately a good place to start a new Scenario
        
        When I click "add-attorney"
        Then I can see popup
        And I can find "form-cancel"
        And I can find "name-title" with 8 options
        # todo - casper just looked for use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"
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
        # Re-add 2cnd attorney
        When I click "add-attorney"
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
        Then I can find save pointing to primary attorney decisions page
        # Check we can see the 2 attorneys listed
        And I see "Mrs Amy Wheeler" in the page text
        And I see "Mr David Wheeler" in the page text
        # re-view 2cnd attorney
        When I click second occurrence of "view-change-attorney"
        Then I can see popup
        And I see "name-title" prepopulated with "Mr"
        And I see form prepopulated with
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
        When I click "form-cancel"
        Then I am taken to the primary attorney page
        When I click "save"
        Then I am taken to the primary attorney decisions page
        # test save without selecting anything
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | How should the attorneys make decisions |
        When I click "how-depends"
        # test save without typing anything in how-details
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Tell us which decisions have to be made jointly, and which can be made jointly and severally |
        When I click "how-jointly-and-severally"
        When I click "save"
        Then I am taken to the replacement attorney page

        # Primary Attorney page tests end here and Replacement Attorney tests start. Ultimately a good place to start a new Scenario
        
        When I click "save"
        Then I am taken to the certificate provider page
        When I click fifth occurrence of "accordion-view-change"
        Then I am taken to the replacement attorney page
        When I click "add-replacement-attorney"
        Then I can see popup
        And I can find "use-my-details"
        And I can find "postcode-lookup"
        And I can find "name-title" with 8 options
        When I select "Ms" on "name-title"
        And I force fill out  
            | name-first | Isobel |
            | name-last | Ward |
            | dob-date-day | 01 |
            | dob-date-month | 02 |
            | dob-date-year | 1937 |
            | address-address1 | 2 Westview |
            | address-address2 | Staplehay |
            | address-address3 | Trull, Taunton, Somerset |
            | address-postcode | TA3 7HF |
        And I click "form-save"
        Then I see "Ms Isobel Ward" in the page text
        When I click "save"
        Then I am taken to the when replacement attorneys step in page
        When I click fifth occurrence of "accordion-view-change"
        Then I am taken to the replacement attorney page
        # Test adding same attorney twice
        When I click "add-replacement-attorney"
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
        Then I see "Ms Isobel Ward" in the page text
        And I see "Mr Ewan Adams" in the page text
        When I click second occurrence of "delete-attorney"
        And I click "delete"
        # Check we are back to 1 attorney listed 
        Then I am taken to the replacement attorney page
        Then I do not see "Mr Ewan Adams" in the page text
        # re-add 2cnd replacement attorney
        When I click "add-replacement-attorney"
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
        # both replacement attorneys can be seen again
        Then I see "Ms Isobel Ward" in the page text
        And I see "Mr Ewan Adams" in the page text
        # re-view 2cnd replacement attorney
        When I click second occurrence of "view-change-attorney"
        Then I can see popup
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
        Then I am taken to the replacement attorney page
        When I click "save"
        Then I am taken to the when replacement attorneys step in page

        # Replacement Attorney details tests end and When Replacement Attorney should Act tests start. Ultimately a good place to start a new Scenario
        
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose how the replacement attorneys should step in |
        When I click "when-depends"
        And I click "save"
        Then I see in the page text
            | There is a problem |
            | Tell us how you'd like the replacement attorneys to step in |
        And I can find "when-details" wrapped with error highlighting
        When I click "when-first"
        And I click "save"
        Then I am taken to the certificate provider page
        When I click sixth occurrence of "accordion-view-change"
        Then I am taken to the when replacement attorneys step in page
        When I click "when-last"
        And I click "save"
        Then I am taken to the how replacement attorneys make decision page

        # When Replacement Attorney should Act tests end and how Replacement Attorney make decisions start. Ultimately a good place to start a new Scenario
        
        # test save without selecting anything
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | How should the replacement attorneys make decisions |
        When I click "how-depends"
        # test save without typing anything in how-details
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Tell us which decisions have to be made jointly, and which can be made jointly and severally |
        When I click "how-jointly-and-severally"
        When I click "save"
        Then I am taken to the certificate provider page
