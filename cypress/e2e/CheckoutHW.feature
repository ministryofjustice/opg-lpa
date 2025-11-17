@PartOfStitchedRun
Feature: Checkout for a Health and Welfare LPA

    I want to Checkout for a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, not repeat application, fee reduction

    @focus @CleanupFixtures
    Scenario: Checkout
        When I log in as appropriate test user
        And I visit the checkout page for the test fixture lpa
        Then I am taken to the checkout page
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        And I see "Application fee: £92 as you are not claiming a reduction" in the page text
        And I see the following summary information
            | Type | Health and welfare | |
            | Donor | | |
            | Name | Mrs Nancy Garrison | donor |
            | Date of birth | 22 October 1988 | |
            | Email address | opglpademo+NancyGarrison@gmail.com | |
            | Address | Bank End Farm House $ Undercliff Drive$ Ventnor, Isle of Wight $ PO38 1UL | |
            | The donor can physically sign or make a mark on the LPA | No | |
            | Life-sustaining treatment | The attorneys can make decisions | life-sustaining |
            | 1st attorney | | |
            | Name | Mrs Amy Wheeler | primary-attorney |
            | Date of birth | 22 October 1988 | |
            | Email address | opglpademo+AmyWheeler@gmail.com | |
            | Address | Brickhill Cottage $ Birch Cross $ Marchington, Uttoxeter, Staffordshire $ ST14 8NX | |
            | 2nd attorney | | |
            | Name | Mr David Wheeler | primary-attorney |
            | Date of birth | 12 March 1972 | |
            | Email address | opglpademo+DavidWheeler@gmail.com | |
            | Address | Brickhill Cottage $ Birch Cross $ Marchington, Uttoxeter, Staffordshire $ ST14 8NX | |
            | Attorney decisions | | |
            | How decisions are made | The attorneys will act jointly and severally | how-primary-attorneys-make-decision |
            | 1st replacement attorney | | |
            | Name | Ms Isobel Ward | replacement-attorney |
            | Date of birth | 1 February 1937 | |
            | Address | 2 Westview $ Staplehay $ Trull, Taunton, Somerset $ TA3 7HF | |
            | 2nd replacement attorney | | |
            | Name | Mr Ewan Adams | replacement-attorney |
            | Date of birth | 12 March 1972 | |
            | Address | 2 Westview $ Staplehay $ Trull, Taunton, Somerset $ TA3 7HF | |
            | Replacement attorney decisions | | |
            | When they step in | The replacement attorneys will only step in when none of the original attorneys can act | when-replacement-attorney-step-in |
            | How decisions are made | The replacement attorneys will act jointly and severally | how-replacement-attorneys-make-decision |
            | Certificate provider | | |
            | Name | Mr Reece Richards | certificate-provider |
            | Address | 11 Brookside $ Cholsey $ Wallingford, Oxfordshire $ OX10 9NN | |
            | Person to notify | | |
            | Name | Sir Anthony Webb | people-to-notify |
            | Address | Brickhill Cottage $ Birch Cross $ Marchington, Uttoxeter, Staffordshire $ BS18 6PL | |
        And I can find "confirm-and-pay-by-card"
        And I can find "confirm-and-pay-by-cheque"

        When I click "confirm-and-pay-by-cheque"
        Then I am taken to the complete page
        And I can find link pointing to "/lp1"
        And I can find link pointing to "/lp3"
        # lines below will be uncommented once we fix issues with pdf generation unreliability
        #And I can get pdf from link containing "Download your print-ready LPA form"
        #And I can get pdf from link containing "Download the letter to send"
