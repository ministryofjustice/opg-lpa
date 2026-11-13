@Remaining2
Feature: Change User Personal Details

    As a user of Make an Lpa I want to be able to change my personal details

    # These scenarios use a freshly created fixture user (not the seeded user) so they are idempotent
    # and cannot interfere with other specs running in parallel against the seeded user.

    Background:
        Given Ordnance Survey postcode lookup responses are stubbed out for good postcode NG2 1AR
        And Ordnance Survey postcode lookup responses are stubbed out for bad postcode blah

    # for Password changes , see seperate Password.feature
    @focus
    Scenario: Change email
        Given I ignore application exceptions
        And I create a new user with 0 LPAs
        When I log in as the newly created fixture user
        And I visit "/user/change-email-address"
        And I see "email_current" prepopulated with the fixture user's email
        When I try to change email address as the fixture user with a mismatch
        Then I am taken to "/user/change-email-address"
        And I see in the page text
            | There is a problem |
            | Enter matching email addresses |
        And I see "Error" in the title
        When I try to change to invalid email address as the fixture user
        Then I am taken to "/user/change-email-address"
        And I see in the page text
            | There is a problem |
            | Enter a valid email address |
        And I see "Error" in the title
        When I try to change email address correctly as the fixture user
        Then I see "We’ve emailed a link to anewemail@digital.justice.gov.uk. You’ll need to click on the link so we know this email address is correct" in the page text

    @focus
    Scenario: Change address
        Given I ignore application exceptions
        And I create a new user with 0 LPAs
        When I log in as the newly created fixture user
        And I visit link containing "Your details"
        Then I am taken to "/user/about-you"
        And I see "address-address1" prepopulated with "Bank End Farm House"
        And I see "address-address2" prepopulated with "Undercliff Drive"
        And I see "address-address3" prepopulated with "Ventnor, Isle of Wight"
        And I see "address-postcode" prepopulated with "PO38 1UL"

        # try an invalid postcode (although we test postcode lookup elsewhere, due to a certain amount of copying in the original codebase we test it here again)
        When I click element marked "Search for UK postcode"
        And I type "blah" into "postcode-lookup" working around cypress bug
        And I see "postcode-lookup" prepopulated within timeout with "blah"
        And I click element marked "Find UK address"
        Then I see "Could not find postcode. Enter your address manually instead of using the postcode lookup." in the page text

        # valid postcode, save changes
        When I type "NG2 1AR" into "postcode-lookup" working around cypress bug
        And I see "postcode-lookup" prepopulated within timeout with "NG2 1AR"
        And I click element marked "Find UK address"
        And I select option "THE PUBLIC GUARDIAN, EMBANKMENT HOUSE, ELECTRIC AVENUE, NOTTINGHAM" of "address-search-result"
        And I see "address-address1" prepopulated with "THE PUBLIC GUARDIAN"
        And I see "address-address2" prepopulated with "EMBANKMENT HOUSE"
        And I see "address-address3" prepopulated with "ELECTRIC AVENUE, NOTTINGHAM"
        And I see "address-postcode" prepopulated with "NG2 1AR"
        When I click "save"
        Then I am taken to the dashboard page

        # double-check saving the address took effect
        When I visit link containing "Your details"
        Then I see "address-address1" prepopulated with "THE PUBLIC GUARDIAN"
        And I see "address-address2" prepopulated with "EMBANKMENT HOUSE"
        And I see "address-address3" prepopulated with "ELECTRIC AVENUE, NOTTINGHAM"
        And I see "address-postcode" prepopulated with "NG2 1AR"

    @RequiresOneLogin
    Scenario: Your details explains that sign-in details are changed in GOV.UK One Login
        Given I create a new user with 1 LPA
        And I log in as the newly created fixture user
        When I visit "/user/about-you"
        When I click element marked "Changing your sign-in details"
        Then I see "GOV.UK One Login (opens in a new tab)" in the page text
        And I do not see "What is your name?" in the page text
        And I see "Deleting your account - find out more" in the page text
