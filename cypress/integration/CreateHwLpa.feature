Feature: Create a Health and Welfare LPA

    I want to create a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
 
    @focus
    Scenario: Create LPA
        Given I log in as appropriate test user
        Then I visit the type page
        # todo - note that the next 2 steps -simulate failing to choose the lpa type, duplicates what happens in CreatePfLpa.feature
        # So these shouldn't need repeating here. However, Casper repeated them, so for now we copy what Casper did. If we
        # delete these , the next step strangely fails, due to subtle difference between what Cypress (and Casper) does
        # and what a real user would do, but only for seeded_test_user , its fine for newly signed-up user, which is really odd
        # This would need sorting before taking these out
        When I click "save"
        Then I see in the page text
            | There was a problem submitting the form |
            | You need to do the following: |
            | Choose a type of LPA |
        And I choose Health and Welfare
        When I click "save"
