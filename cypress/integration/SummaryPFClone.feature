@CreateLpa
Feature: View Summary for Create a Property and Finance LPA

    I want to view the summary for a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, single attorney, cert provider, people to notify, instructions, preferences

    @focus
    Scenario: View Summary
        When I log in as appropriate test user
        And I visit the summary page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        Then I am taken to the summary page
        And I see the following summary information
            | Type | Property and finance | |
            | Donor | | |
            | Name | Mrs Nancy Garrison | donor |
            | Date of birth | 22 October 1988 | |
            | Email address | opglpademo+NancyGarrison@gmail.com | |
            | Address | Bank End Farm House $ Undercliff Drive$ Ventnor, Isle of Wight $ PO38 1UL | |
            | The donor can physically sign or make a mark on the LPA | No | |
            | When LPA starts |  As soon as it's registered (and with the donor's consent) | when-lpa-starts |
            | Attorney | | |
            | Name | Standard Trust | primary-attorney |
            | Company number | 678437685 | |
            | Email address | opglpademo+trustcorp@gmail.com | |
            | Address | 1 Laburnum Place $ Sketty $ Swansea, Abertawe $ SA2 8HT | |
            | Replacement attorney | No replacement attorneys | replacement-attorney |
            | Certificate provider | | |
            | Name | Mr Reece Richards | certificate-provider |
            | Address | 11 Brookside $ Cholsey $ Wallingford, Oxfordshire $ OX10 9NN | |
        And I can find draft download link
        And I can get pdf from link containing "download a preview"
        When I click "continue"
        Then I am taken to the applicant page
