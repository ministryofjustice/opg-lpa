@PartOfStitchedRun
Feature: Add donor to Health and Welfare LPA

    I want to add a donor to a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture
        And Ordnance Survey postcode lookup responses are stubbed out for good postcode B1 1TF

    @focus @CleanupFixtures
    Scenario: Add Donor to LPA
        When I log in as appropriate test user
        And I visit the donor page for the test fixture lpa
        Then I see "This LPA covers health and welfare" in the page text
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove
        # save button should be missing initially
        And I cannot find "save-and-continue"
        Then the page matches the "donor" baseline image
        When I click "add-donor"
        Then I can find "form-donor"
        And accessibility checks should pass for "donorHW page with popup open"
        # todo - casper merely checked for existence of use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"
        # casper simply checked for 8 options so we do too, but we may ultimately wish to check the values
        And I can find "name-title" with 8 options
        When I type "B1 1TF" into "postcode-lookup" working around cypress bug
        # cypress is not reliable at filling in postcode fully before hitting next button, so, ensure it is now filled in
        And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
        And I click element marked "Find UK address"
        # casper simply checked for 6 options. PAF was updated to remove some of the addresses from the tested postcode so we check for 3. We may ultimately wish to check the values
        Then I can find "address-search-result" with 6 options

        # Check error message when donor > 100 years old
        When I force fill out
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1808 |
        And I select "Mr" on "name-title"
        Then I see "By saving this section, you confirm that the donor is more than 100 years old. If not, please change the date." in the page text

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
        And I check "cannot-sign"
        Then the page matches the "donor-form" baseline image
        And I click "form-save"
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
        When I click "form-cancel"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-donor"
        When I click "save-and-continue"
        Then I am taken to the life sustaining page
        And I see "Who does the donor want to make decisions about life-sustaining treatment?" in the page text
        # in this test we check CanSustainLife-0 (no option) exists, then a few lines down we actually click canSustainLife-1 (yes)
        And I can find "canSustainLife-0"
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Select if the donor gives or does not give their attorneys authority to consent to life-sustaining treatment |
        And I see "Error" in the title
        When I check "canSustainLife-1"
        Then the page matches the "life-sustaining-treatment" baseline image
        And I click "save"
        Then I am taken to the primary attorney page
        And I cannot find "save"
