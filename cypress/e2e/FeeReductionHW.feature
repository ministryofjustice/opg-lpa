@PartOfStitchedRun
Feature: Fee Reduction for a Health and Welfare LPA

    I want to set Fee Reduction for a Health and Welfare LPA

    Background:
        Given I ignore application exceptions
        And I create HW LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application

    @focus @CleanupFixtures
    Scenario: Fee Reduction
        When I log in as appropriate test user
        And I visit the fee reduction page for the test fixture lpa
        Then I am taken to the fee reduction page
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove

        And I can find "reducedFeeReceivesBenefits"
        And I can find "reducedFeeUniversalCredit"
        And I can find "reducedFeeLowIncome"
        And I can find "notApply"

        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Select if the donor does or does not want to apply for a fee reduction |

        When I check "reducedFeeLowIncome"
        Then the page matches the "fee-reduction" baseline image
        Then I see "The documents must have the donor’s title, full name, address and postcode printed on them and they must be from the current tax year. Tax years run from 6 April one year to 5 April the next year." in the page text
        And I should not see "Because Universal Credit is in its trial phase and replaces several existing benefits, we're looking at fee reductions on a case-by-case basis." in the page text
        And I should not see "To apply to pay no fee, you must send us a ‘fee remissions and exemptions form’ and copies of letters from the Department for Work and Pensions (DWP) or the benefit provider as proof that the donor is receiving benefits." in the page text

        When I check "notApply"
        And I click "save"
        Then I am taken to the checkout page
