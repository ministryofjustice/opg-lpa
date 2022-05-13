@StitchedClone
Feature: Clone Property and Finance LPA starting from the Type page

    I want to go to the type page and clone a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I set cloned to true
        And I log in as second seeded user
        Then I am taken to the dashboard page

    @focus @CleanupFixtures
    Scenario: Choose Property and Finance as Lpa Type
        # we should find at least one existing lpa with related links. we simply click the first one we find , to clone it
        Then I can find "check-signing-dates"
        And I can find "delete-lpa"
        When I click occurrence 0 of "reuse-lpa-details"
        Then I am taken to the type page for cloned lpa
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page
        And I see "This LPA covers property and financial affairs" in the page text

        # save button should be missing initially
        And I cannot find "save-and-continue"
        When I click "add-donor"
        # todo - casper merely checked for existence of use-my-details. We need ultimately to actually test this
        Then I can find use-my-details if lpa is new
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-donor" 
        And accessibility checks should pass for "donorPF page with popup open"
        # ensure we are on the donor form , in case re-use details form was previously shown
        When I type "B1 1TF" into "postcode-lookup" working around cypress bug
        # cypress is not reliable at filling in postcode fully before hitting next button, so, ensure it is now filled in
        And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
        And I click element marked "Find UK address"
        # casper simply checked for 6 options so we do too, but we may ultimately wish to check the values
        Then I can find "address-search-result" with 6 options
        # casper simply checked for 8 options so we do too, but we may ultimately wish to check the values
        And I can find "name-title" with 8 options
        When I force fill out
            | name-first | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | name-last | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | dob-date-day | 22 |
            | dob-date-month | 10 |
            | dob-date-year | 1988 |
            | email-address | opglpademo+NancyGarrison@gmail.com |
            | address-address1 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address2 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address3 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-postcode | PO38 1UL |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the donor's title |
            | Enter a first name that's less than 54 characters long |
            | Enter a last name that's less than 62 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        When I select "Mrs" on "name-title"
        And I force fill out
            | name-first | Nancy |
            | name-last | Garrison |
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1988 |
            | email-address| opglpademo+NancyGarrison@gmail.com |
            | address-address1| Bank End Farm House |
            | address-address2| Undercliff Drive |
            | address-address3| Ventnor, Isle of Wight |
            | address-postcode| PO38 1UL |
        And I check "can-sign"
        And I click "form-save"
        Then I cannot find "form-donor" 
        Then I can find "save-and-continue"
        And I cannot find "add-donor"
        And I see "Mrs Nancy Garrison" in the page text
        # following line uses force click because view-change-donor button is partly obscured
        When I force click "view-change-donor"
        Then I can find "form-donor" 
        And I see "name-title" prepopulated with "Mrs"
        And I see form prepopulated with
            | name-first | Nancy |
            | name-last | Garrison |
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1988 |
            | email-address| opglpademo+NancyGarrison@gmail.com |
            | address-address1| Bank End Farm House |
            | address-address2| Undercliff Drive |
            | address-address3| Ventnor, Isle of Wight |
            | address-postcode| PO38 1UL |
        When I click "form-cancel"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-donor" 
        When I click "save-and-continue"
        And I am taken to the when lpa starts page
        When I click "save"
        Then I see in the page text
            | There is a problem |
            | Choose when your LPA can be used |
        When I check "when-no-capacity"
        And I click "save"
        Then I am taken to the primary attorney page
        And I see "The LPA starts only if the donor does not have mental capacity" in the page text
        And I do not see "The LPA starts as soon as it's registered (with the donor's consent)" in the page text
        And I cannot find "save"
        When I click occurrence 1 of "accordion-view-change"
        Then I am taken to the when lpa starts page
        When I check "when-now"
        And I click "save"
        Then I am taken to the primary attorney page
        And I see "The LPA starts as soon as it's registered (with the donor's consent)" in the page text
        And I do not see "The LPA starts only if the donor does not have mental capacity" in the page text
        When I click "add-attorney"
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-attorney"
        And I can find "form-cancel"
        And I can find "postcode-lookup"
        And I can find "name-title" with 8 options
        And I can find use-my-details if lpa is new
        And I click "use-trust-corporation"
        When I force fill out
            | name | Standard Trust |
            | number | 678437685 |
            | email-address| opglpademo+trustcorp@gmail.com |
            | address-address1 | 1 Laburnum Place |
            | address-address2 | Sketty |
            | address-address3 | Swansea, Abertawe |
            | address-postcode | SA2 8HT |
        And I click "form-save"
        Then I see "Standard Trust" in the page text
        When I click "save"
        Then I am taken to the replacement attorney page

        # note that for the clone test, we do not add a replacement attorney, as that is already covered by PF test, the scenario we cover here is not having replacement attorney(s)
        When I click "add-replacement-attorney"
        And I can find use-my-details if lpa is new
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-attorney"
        And I can find "postcode-lookup"
        And I can find "name-title" with 8 options
        When I click "form-cancel"
        And I cannot find "form-attorney"
        And I click "save"
        Then I am taken to the certificate provider page


        When I click "add-certificate-provider"
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-certificate-provider"
        And I can find "form-cancel"
        And I can find "name-title" with 8 options
        # todo - casper just looked for use-my-details. We need ultimately to actually test this
        And I can find use-my-details if lpa is new
        When I force fill out
            | name-first | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | name-last | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address1 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address2 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-address3 | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
            | address-postcode | OX10 9NN |
        And I click "form-save"
        Then I see in the page text
            | There is a problem |
            | Enter the certificate provider's title |
            | Enter a first name that's less than 51 characters long |
            | Enter a last name that's less than 51 characters long |
            | Change address line 1 so that it has fewer than 51 characters |
            | Change address line 2 so that it has fewer than 51 characters |
            | Change address line 3 so that it has fewer than 51 characters |
        When I select "Mr" on "name-title"
        And I force fill out
            | name-first | Reece |
            | name-last | Richards |
            | address-address1 | 11 Brookside |
            | address-address2 | Cholsey |
            | address-address3 | Wallingford, Oxfordshire |
        And I click "form-save"
        # check certificate provider is listed and save points to people to notify page
        Then I see "Mr Reece Richards" in the page text
        And I can find save pointing to people to notify page
        # Casper checked for existence of delete link, here we click it then cancel, which is more thorough
        When I click "delete-certificate-provider"
        And I click "cancel"
        And I click "view-change-certificate-provider"
        Then I can find "form-certificate-provider"
        And I see "name-title" prepopulated with "Mr"
        And I see form prepopulated with
            | name-first | Reece |
            | name-last | Richards |
            | address-address1 | 11 Brookside |
            | address-address2 | Cholsey |
            | address-address3 | Wallingford, Oxfordshire |
            | address-postcode | OX10 9NN |
        And I click "form-cancel"
        When I click "save"
        Then I am taken to the people to notify page

        # note that for a clone, we do not actually add a person to notify, as this is already tested in PersonToNotifyPF.feature.
        When I click "add"
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-people-to-notify"
        When I click "form-cancel"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-people-to-notify" 
        And I click "save"
        Then I am taken to the instructions page

        Then I can find "instruction" but it is not visible
        And I can find "preferences" but it is not visible
        When I click "add-extra-preferences"
        Then I can find "instruction" and it is visible
        And I can find "preferences" and it is visible
        And I fill out
            | instruction | Lorem Ipsum |
            | preferences | Neque porro quisquam |
        When I click "save"
        Then I am taken to the applicant page
        When I click the last occurrence of "accordion-view-change"
        Then I see in the page text
            | Lorem Ipsum |
            | Neque porro quisquam |
        When I click "save"
        Then I am taken to the applicant page
        When I visit link containing "preview the LPA"


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
        When I click "continue"
        Then I am taken to the applicant page

        Then I am taken to the applicant page
        # select the attorney as applicant
        When I check "whoIsRegistering-1"
        And I click "save"
        Then I am taken to the correspondent page

        # second test , choose certificate provider as correspondent
        And I can find "change-correspondent" with data-inited
        When I click "change-correspondent"
        Then I can find "form-reuse-details"
        And I see "Which details would you like to reuse?" in the page text
        When I check "reuse-details-3"
        And I click "continue"
        And I see "Mr Reece Richards" in the page text
        And I see "11 Brookside, Cholsey, Wallingford, Oxfordshire, OX10 9NN" in the page text
        And I check "contactByPost" 
        When I click "save"
        Then I am taken to the who are you page
        And I see "The LPA will be sent to Mr Reece Richards" in the page text

        # third test - switch to attorney as correspondent
        When I click the last occurrence of "accordion-view-change"
        Then I am taken to the correspondent page
        And I can find "change-correspondent" with data-inited
        When I click "change-correspondent"
        Then I can find "form-reuse-details"
        When I check "reuse-details-2"
        And I click "continue"
        Then I can find "form-correspondent"
        And I click "form-save"
        # next line is essential, cypress needs the form not to be there before it can reliably find save button in CI
        Then I cannot find "form-correspondent"
        And I see "Standard Trust" in the page text
        And I see "1 Laburnum Place, Sketty, Swansea, Abertawe, SA2 8HT" in the page text
        When I click "save"
        Then I am taken to the who are you page
        And I see "The LPA will be sent to Standard Trust" in the page text

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
        When I click the last occurrence of "accordion-view-change"
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
        When I check "reducedFeeReceivesBenefits"
        And I click "save"
        Then I am taken to the checkout page
        And I see "Application fee: £0 as the donor is claiming an eligible benefit" in the page text

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
            | Person to notify | No people to notify | people-to-notify |
            | Preferences | Neque porro quisquam | instructions |
            | Instructions | Lorem Ipsum | instructions |
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
