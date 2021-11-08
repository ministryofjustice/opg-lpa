Feature: PDF Generation for Property and Finance LPA

    I want to generate a PDF for a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences

    @focus 
    Scenario: Generate PDF
        When I log in as appropriate test user
        And I visit view docs page for test lpa "91155453023"
        And I can get pdf from link containing "Download your print-ready LPA form"
