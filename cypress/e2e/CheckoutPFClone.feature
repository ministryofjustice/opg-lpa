@PartOfStitchedRun
Feature: Checkout for a Property and Finance LPA

    I want to Checkout for a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, single attorney, cert provider, instructions, preferences, applicant, trustcorp as correspondent, who are you as first primary attorney, not repeat application, on benefits fee reduction

    @focus @CleanupFixtures
    Scenario: Checkout
        When I log in as appropriate test user
        And I visit the checkout page for the test fixture lpa
        Then I am taken to the checkout page

        # in local dev, we have to go to the payment page again, select the £0 payment option,
        # then save and confirm; the checkout page requires us to have just visited the payment
        # page before reaching this one, otherwise we get an irrelevant prompt about the certificate
        # provider; this is because the checkout page assumes that if the LPA is incomplete, it's
        # because we skipped the certificate provider selection
        When I visit the fee reduction page for the test fixture lpa
        And I check "reducedFeeReceivesBenefits"
        And I click "save"

        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove
        Then I see the following summary information
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
            | Person to notify | No people to notify | people-to-notify |
            | Preferences | Some preferences | instructions |
            | Instructions | Some instructions | instructions |
            | Who is registering the LPA | Standard Trust | applicant |
            | Correspondent | | |
            | Company name | Standard Trust | correspondent |
            | Email address | opglpademo+trustcorp@gmail.com | |
            | Address | 1 Laburnum Place $ Sketty $ Swansea, Abertawe $ SA2 8HT | |
            | Repeat application | This is not a repeat application | repeat-application |
            | Application fee | Application fee: £0 as the donor is claiming an eligible benefit | fee-reduction |

        When I click "confirm-and-finish"
        Then I am taken to the complete page
        And I can find link pointing to "/lp1"
        # note there is not /lp3 link as there is no person to notify
        # note that /lpa120 link only appears when fee reduction is requested
        And I can find link pointing to "/lpa120"
        # lines below will be uncommented once we fix issues with pdf generation unreliability
        #And I can get pdf from link containing "Download your print-ready LPA form"
        #And I can get pdf from link containing "Download the letter to send"
