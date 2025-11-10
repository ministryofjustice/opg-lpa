@PartOfStitchedRun
Feature: Add attorneys to a Health and Welfare LPA

    I want to add attorneys to a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with a donor

    @focus @CleanupFixtures
    Scenario: Add Attorneys
        When I log in as appropriate test user
        And I visit the primary attorney page for the test fixture lpa

        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        When I click "add-attorney"
        Then I can find "form-attorney"
        And I can find "form-cancel"
        And I can find "name-title" with 8 options
        # todo - casper just looked for use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"

        # check client-side validation of duplicate names - add attorney with same name as donor
        When I force fill out
            | name-first | Nancy |
            | name-last | Garrison |
        # shift focus to the title drop-down to trigger the client-side duplicate name validation
        And I select "Mrs" on "name-title"
        Then I see "The donor's name is also Nancy Garrison. The donor cannot be an attorney." in the page text
        And I see "By saving this section, you are confirming that these are 2 different people with the same name." in the page text

        # check client-side age validation - under 18
        When I force fill out "dob-date-year" with the value of the year 16 years ago
        And I force fill out
            | dob-date-day | 21  |
            | dob-date-month | 9 |
        # shift focus to the title drop-down to trigger the client-side age validation
        And I select "Mrs" on "name-title"
        Then I see "This attorney is under 18. I understand that the attorney must be at least 18 on the date the donor signs the LPA, otherwise the LPA will be rejected." in the page text

        # check client-side age validation - over 100
        When I force fill out
            | dob-date-day | 21  |
            | dob-date-month | 9 |
            | dob-date-year | 1910 |
        # shift focus to the title drop-down to trigger the client-side age validation
        And I select "Mrs" on "name-title"
        Then I see "By saving this section, you confirm that this attorney is more than 100 years old. If not, please change the date." in the page text

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
        # test client-side validation of duplicate people (same attorney twice)
        Then I see "There is also an attorney called Amy Wheeler. A person cannot be named as an attorney twice on the same LPA." in the page text

        # Add 2nd attorney
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

        # Delete 2nd attorney
        When I click occurrence 1 of "delete-attorney"
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
        When I click occurrence 1 of "view-change-attorney"
        Then I can find "form-attorney"
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
