Feature: PDF Generation for Health and Welfare LPA

    I want to generate a PDF for a Health and Welfare LPA

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: Generate PDF
        When I log in as seeded user
        And I visit "/lpa/68582508781"
        Then I can get pdf from link containing "Download your print-ready LPA form"

    @focus
    Scenario: Deeplinks work
        Given I visit "/lpa/68582508781/view-docs" without being logged in
        Then I should be on "/login"
        When I log in as seeded user on the current page
        Then I should be on "/lpa/68582508781/view-docs"
