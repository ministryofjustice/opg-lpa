Feature: PDF Generation for Health and Welfare LPA

    I want to generate a PDF for a Health and Welfare LPA

    Background:
        Given I ignore application exceptions

    @focus 
    Scenario: Generate PDF
        When I log in as appropriate test user
        And I visit view docs page for test lpa "68582508781"
        And I can get pdf from link containing "Download your print-ready LPA form"
