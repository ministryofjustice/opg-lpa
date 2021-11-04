Feature: PDF Generation for Health and Welfare LPA

    I want to generate a PDF for a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences

    @focus 
    Scenario: Generate PDF
        When I log in as appropriate test user
        And I visit the summary page for the test fixture lpa
        Then I am taken to the summary page
        And I can find draft download link
        And I can get pdf from link containing "download a preview"
        When I click "continue"
        Then I am taken to the applicant page
