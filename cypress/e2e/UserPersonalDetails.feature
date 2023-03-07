Feature: Change User Personal Details

    As a user of Make an Lpa I want to be able to change my personal details

    # NOTE: this test is not idempotent - you will get a failure the second time you run it
    # TODO reset address in test data before running the test

    Background:
        Given Ordnance Survey postcode lookup responses are stubbed out for good postcode B1 1TF
        And Ordnance Survey postcode lookup responses are stubbed out for good postcode NG2 1AR
        And Ordnance Survey postcode lookup responses are stubbed out for bad postcode blah

    # for Password changes , see seperate Password.feature
    @focus
    Scenario: Change email
        Given I ignore application exceptions
        When I log in as seeded user
        And I visit link containing "Your details"
        Then I am taken to "/user/about-you"
        When I visit link containing "Change Email Address"
        Then I am taken to "/user/change-email-address"
        And I see "email_current" prepopulated with "seeded_test_user@digital.justice.gov.uk"
        When I try to change email address with a mismatch
        Then I am taken to "/user/change-email-address"
        And I see in the page text
            | There is a problem |
            | Enter matching email addresses |
        And I see "Error" in the title
        When I try to change to invalid email address
        Then I am taken to "/user/change-email-address"
        And I see in the page text
            | There is a problem |
            | Enter a valid email address |
        And I see "Error" in the title
        When I try to change email address correctly
        Then I see "We've emailed a link to anewemail@digital.justice.gov.uk. You'll need to click on the link so we know this email address is correct" in the page text

    @focus
    Scenario: Change address
        Given I ignore application exceptions
        When I log in as seeded user
        And I visit link containing "Your details"
        Then I am taken to "/user/about-you"
        And I see "address-address1" prepopulated with "THE OFFICE OF THE PUBLIC GUARDIAN"
        And I see "address-address2" prepopulated with "THE AXIS"
        And I see "address-address3" prepopulated with "10 HOLLIDAY STREET, BIRMINGHAM"
        And I see "address-postcode" prepopulated with "B1 1TF"

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

        # change address back to original
        When I visit link containing "Your details"
        And I click element marked "Search for UK postcode"
        And I type "B1 1TF" into "postcode-lookup" working around cypress bug
        And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
        And I click element marked "Find UK address"
        And I select option "THE OFFICE OF THE PUBLIC GUARDIAN, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM" of "address-search-result"
        And I see "address-address1" prepopulated with "THE OFFICE OF THE PUBLIC GUARDIAN"
        And I see "address-address2" prepopulated with "THE AXIS"
        And I see "address-address3" prepopulated with "10 HOLLIDAY STREET, BIRMINGHAM"
        And I see "address-postcode" prepopulated with "B1 1TF"
        When I click "save"
        Then I am taken to the dashboard page

        # double-check saving address back took effect
        When I visit link containing "Your details"
        And I see "address-address1" prepopulated with "THE OFFICE OF THE PUBLIC GUARDIAN"
        And I see "address-address2" prepopulated with "THE AXIS"
        And I see "address-address3" prepopulated with "10 HOLLIDAY STREET, BIRMINGHAM"
        And I see "address-postcode" prepopulated with "B1 1TF"
