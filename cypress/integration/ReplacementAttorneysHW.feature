@CreateLpa
Feature: Add Replacement Attorneys to a Health and Welfare LPA

    I want to Add Replacement Attorneys to a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with a donor and attorneys

    @focus, @CleanupFixtures
    Scenario: Add Replacment Attorneys
        When I log in as appropriate test user
        And I visit the replacement attorney page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        When I click "save"
        Then I am taken to the certificate provider page
        When I click occurrence 4 of "accordion-view-change"
        Then I am taken to the replacement attorney page
        When I click "add-replacement-attorney"
        Then I can find "form-attorney"
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
        Then I cannot find "form-attorney" 
        And I see "Ms Isobel Ward" in the page text
        When I click "save"
        Then I am taken to the when replacement attorneys step in page
        When I click occurrence 4 of "accordion-view-change"
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
        Then I cannot find "form-attorney" 
        And I see "Ms Isobel Ward" in the page text
        And I see "Mr Ewan Adams" in the page text
        When I click occurrence 1 of "delete-attorney"
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
        Then I cannot find "form-attorney" 
        # both replacement attorneys can be seen again
        And I see "Ms Isobel Ward" in the page text
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
        Then I am taken to the replacement attorney page
        When I click "save"
        Then I am taken to the when replacement attorneys step in page

        # Replacement Attorney details tests end and When Replacement Attorney should Act tests start. 

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

