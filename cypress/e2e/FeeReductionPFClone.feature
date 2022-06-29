@PartOfStitchedRun
Feature: Fee Reduction for a Property and Finance LPA

    I want to set Fee Reduction for a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application

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
        When I check "reducedFeeReceivesBenefits"
        And I click "save"
        Then I am taken to the checkout page
        And I see "Application fee: £0 as the donor is claiming an eligible benefit" in the page text
