@CreateLpa
Feature: Who Are You for a Health and Welfare LPA

    I want to set Who Are You for a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant

    @focus
    Scenario: Who Are You
        When I log in as appropriate test user

        # THIS WILL GO -  fixture will need correspondent set
        And I visit the correspondent page for the test fixture lpa
        Then I am taken to the correspondent page
        When I click "save"
        Then I am taken to the who are you page
        # end of this will go

        # ultimately how it should be starts here
        And I visit the who are you page for the test fixture lpa
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        Then I am taken to the who are you page
        And I can find "who"
        And I can find "who-friend-or-family"
        And I can find "who-finance-professional"
        And I can find "who-legal-professional"
        And I can find "who-estate-planning-professional"
        And I can find "who-digital-partner"
        And I can find "who-charity"
        And I can find "who-organisation"
        And I can find "who-other"
        And I can find "who-notSaid"
        When I click "save"
        Then I see "There is a problem" in the page text
        When I check "who"
        And I click "save"
        Then I am taken to the repeat application page
        When I click occurrence 12 of "accordion-view-change"
        Then I am taken to the who are you page
        And I see "Thanks, you have already answered this question" in the page text
        When I click "continue"
        Then I am taken to the repeat application page
        # repeatCaseNumber should be hidden initially
        And I can find hidden "repeatCaseNumber"

        When I check "isRepeatApplication-is-repeat"
        And I click "save"
        When I click element marked "Confirm and continue"
        Then I see in the page text
            | There is a problem |
            | If you are making a repeat application, you need to enter the case number given to you by the Office of the Public Guardian. | 

        # for PF we test typing in a case number. The other scenario where this is not a repeat, is covered here
        When I check "isRepeatApplication-is-new"
        And I click "save"
        Then I am taken to the fee reduction page

        And I can find "reducedFeeReceivesBenefits"
        And I can find "reducedFeeUniversalCredit"
        And I can find "reducedFeeLowIncome"
        And I can find "notApply"

        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Select if the donor does or does not want to apply for a fee reduction |

        When I check "reducedFeeLowIncome"
        Then I see "The documents must have the donor’s title, full name, address and postcode printed on them and they must be from the current tax year. Tax years run from 6 April one year to 5 April the next year." in the page text
        And I should not see "Because Universal Credit is in its trial phase and replaces several existing benefits, we're looking at fee reductions on a case-by-case basis." in the page text
        And I should not see "To apply to pay no fee, you must send us a ‘fee remissions and exemptions form’ and copies of letters from the Department for Work and Pensions (DWP) or the benefit provider as proof that the donor is receiving benefits." in the page text

        When I check "notApply"
        And I click "save"
        Then I am taken to the checkout page
        And I see "Application fee: £82 as you are not claiming a reduction" in the page text
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

        # temporarily till make this a seperate feature, hit cheque button
        When I click "confirm-and-pay-by-cheque"
        Then I am taken to the complete page
        And I can find link pointing to "/lp1"
        And I can find link pointing to "/lp3"
        And I can find link pointing to "/lpa120"
        # lines below will be uncommented once we fix issues with pdf generation unreliability
        And I can get pdf from link containing "Download your print-ready LPA form"
        And I can get pdf from link containing "Download the letter to send"
