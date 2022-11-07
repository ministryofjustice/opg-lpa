Feature: PDF Generation for Health and Welfare LPA

    I want to generate a PDF for a Health and Welfare LPA

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: Generate PDF
        When I log in as seeded user
        And I visit "/lpa/68582508781"
        Then I can get pdf from link containing "Download your print-ready LPA form"
