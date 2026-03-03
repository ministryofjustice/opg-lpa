@PartOfStitchedRun
Feature: Add People to Notify to a Health and Welfare LPA

    I want to add People to Notify to a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider

    @focus @CleanupFixtures
    Scenario: Add person to notify
        When I log in as appropriate test user
        And I visit the people to notify page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        Then the page matches the "add-people-to-notify" baseline image
        When I click "add"
        Then I can find "form-people-to-notify"
        And I can find "form-cancel"
        And I can find "name-title" with 8 options
        # todo - casper just looked for use-my-details. We need ultimately to actually test this
        And I can find "use-my-details"
        When I select "Other" on "name-title"
        And I force fill out
            | name-title | Sir |
            | name-first | Anthony |
            | name-last | Webb |
            | address-address1 | Brickhill Cottage |
            | address-address2 | Birch Cross |
            | address-address3 | Marchington, Uttoxeter, Staffordshire |
            | address-postcode | BS18 6PL |
        Then the page matches the "add-people-to-notify-form" baseline image
        And I click "form-save"
        Then I see "Sir Anthony Webb" in the page text
        When I click "view-change"
        Then I can find "form-people-to-notify"
        And I see form prepopulated with
            | name-title | Sir |
            | name-first | Anthony |
            | name-last | Webb |
            | address-address1 | Brickhill Cottage |
            | address-address2 | Birch Cross |
            | address-address3 | Marchington, Uttoxeter, Staffordshire |
            | address-postcode | BS18 6PL |
        And I click "form-cancel"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-people-to-notify"
        When I click "save"
        Then I am taken to the instructions page
