Feature: PDF Generation for Property and Finance LPA

    I want to generate a PDF for a Property and Finance LPA

    Background:
        Given I ignore application exceptions

    @focus 
    Scenario: Generate PDF
        When I log in as seeded user
        And I click "view-or-continue-lpa-91155453023"
        Then I can get pdf from link containing "Download your print-ready LPA form"
